<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role; // extend Spatie\Permission\Models\Role
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /**
     * Unified table: Employee Directory + User account (password/roles) in one page
     * Route: GET /admin/settings/access/users
     */
    public function index(Request $req)
    {
        $unitId = auth()->user()?->unit_id;
        $q = trim((string) $req->get('q', ''));

        // Roles options (scoped team/null) untuk checklist di modal
        $roles = Role::query()
            ->where('guard_name','web')
            ->where(function($w) use ($unitId){
                $w->whereNull('unit_id')->orWhere('unit_id',$unitId);
            })
            ->orderBy('name','asc')
            ->get(['id','name']);

        // Unified rows: employees LEFT JOIN users by employee_id
        $rows = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('users as us', 'us.employee_id', '=', 'e.employee_id') // akun (optional)
            // BASE CONSTRAINTS (PTSI only, exclude outsourcing, exclude KSO SCI-SI)
            ->where(function ($w) {
                $w->whereNull('e.company_name')
                  ->orWhereRaw("TRIM(e.company_name) = ''")
                  ->orWhereRaw("LOWER(TRIM(e.company_name)) IN ('pt surveyor indonesia','pt. surveyor indonesia')");
            })
            ->where(function ($w) {
                $w->whereNull('e.employee_status')
                  ->orWhereRaw("TRIM(e.employee_status) = ''")
                  ->orWhereRaw("LOWER(e.employee_status) NOT LIKE '%alih%'")
                  ->whereRaw("LOWER(e.employee_status) NOT LIKE '%outsour%'");
            })
            ->where(function ($w) {
                $normUnit = "LOWER(REPLACE(TRIM(COALESCE(u.name, e.latest_jobs_unit, '')),'–','-'))";
                $w->whereRaw("$normUnit NOT IN ('kso sci-si','kso sci - si','kso sci si')");
            })
            ->when($q !== '', function ($qb) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $qb->where(function ($w) use ($like) {
                    $w->where('e.employee_id', 'like', $like)
                      ->orWhere('e.id_sitms', 'like', $like)
                      ->orWhere('p.full_name', 'like', $like)
                      ->orWhere('pos.name', 'like', $like)
                      ->orWhere('u.name', 'like', $like)
                      ->orWhere('e.latest_jobs_unit', 'like', $like)
                      ->orWhere('e.latest_jobs_title', 'like', $like)
                      ->orWhere('e.home_base_city', 'like', $like)
                      ->orWhere('e.home_base_province', 'like', $like)
                      ->orWhere('e.email', 'like', $like)
                      ->orWhere('p.email', 'like', $like);
                });
            })
            ->selectRaw("
                e.id as employee_pk,
                e.employee_id,
                e.id_sitms,
                COALESCE(p.full_name, e.employee_id, CAST(e.id AS CHAR))  as full_name,
                COALESCE(pos.name, e.latest_jobs_title)                    as job_title,
                COALESCE(u.name,  e.latest_jobs_unit)                     as unit_name,
                COALESCE(e.email, p.email)                                as employee_email,
                e.employee_status                                         as employee_status,
                us.id                                                     as user_id,
                us.email                                                  as user_email,
                us.name                                                   as user_name,
                us.unit_id                                                as user_unit_id
            ")
            ->orderBy('full_name','asc')
            ->get()
            ->map(function($r){
                // normalisasi key utk front-end
                $r->employee_key = $r->employee_id ?: ($r->id_sitms ?: (string)$r->employee_pk);
                return $r;
            });

        // Prefetch roles per user scoped by team (1 query, lalu group)
        $userIds = $rows->pluck('user_id')->filter()->unique()->values();
        $userRolesMap = [];
        if ($userIds->isNotEmpty()) {
            $pivot = DB::table('model_has_roles as mhr')
                ->join('roles as r','r.id','=','mhr.role_id')
                ->where('mhr.model_type', '=', User::class)
                ->whereIn('mhr.model_id', $userIds)
                ->where('r.guard_name','=','web')
                ->where(function($w) use ($unitId){
                    $w->whereNull('r.unit_id')->orWhere('r.unit_id',$unitId);
                })
                ->select('mhr.model_id as user_id','r.id as role_id','r.name as role_name')
                ->orderBy('r.name','asc')
                ->get()
                ->groupBy('user_id');

            foreach ($pivot as $uid => $list) {
                $userRolesMap[$uid] = [
                    'ids'   => $list->pluck('role_id')->values()->all(),
                    'names' => $list->pluck('role_name')->values()->all(),
                ];
            }
        }

        return view('admin.users.index', [
            'rows'  => $rows,
            'roles' => $roles,
            'userRolesMap' => $userRolesMap,
            'q'     => $q,
        ]);
    }

    /** Create account + assign roles (teams=true) — password default "password" bila kosong */
    public function store(Request $req)
    {
        $data = $req->validate([
            'name'        => ['required','string','max:255'],
            'email'       => ['required','email','max:255', Rule::unique('users','email')],
            'password'    => ['nullable','string','min:8'],
            'employee_id' => ['nullable','string','max:255'], // link ke employee
            'roles'       => ['array'],
            'roles.*'     => ['integer','exists:roles,id'],
        ]);

        $user = new User();
        $user->name       = $data['name'];
        $user->email      = $data['email'];
        $user->password   = \Illuminate\Support\Facades\Hash::make($data['password'] ?? 'password'); // default
        $user->unit_id    = auth()->user()?->unit_id; // scope team default
        if (!empty($data['employee_id'])) {
            $user->employee_id = $data['employee_id'];
        }
        $user->save();

        $this->withTeamContext(function() use ($user, $data){
            if (!empty($data['roles'])) {
                $roleModels = Role::query()->whereIn('id',$data['roles'])->get();
                $user->syncRoles($roleModels->pluck('name')->all());
            } else {
                $user->syncRoles([]);
            }
        }, $user->unit_id);

        return back()->with('ok','Account created (default password: "password").');
    }

    /** Update password (optional) + sync roles (teams=true) */
    public function update(Request $req, User $user)
    {
        $data = $req->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:8'],
            'roles'    => ['array'],
            'roles.*'  => ['integer','exists:roles,id'],
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $this->withTeamContext(function() use ($user, $data){
            if (array_key_exists('roles', $data)) {
                $roleModels = Role::query()->whereIn('id', $data['roles'] ?? [])->get();
                $user->syncRoles($roleModels->pluck('name')->all());
            }
        }, $user->unit_id);

        return back()->with('ok','Account updated.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors('Tidak dapat menghapus akun sendiri.');
        }
        $user->delete();
        return back()->with('ok','User deleted.');
    }

    public function roleOptions(Request $req)
    {
        $unitId = auth()->user()?->unit_id;
        $roles = Role::query()
            ->where('guard_name','web')
            ->where(function($q) use ($unitId){
                $q->whereNull('unit_id')->orWhere('unit_id', $unitId);
            })
            ->orderBy('name','asc')
            ->get(['id','name']);

        return response()->json($roles->map(fn($r)=>['id'=>$r->id,'name'=>$r->name]));
    }

    /** Helper: run callback within Spatie teams context */
    protected function withTeamContext(\Closure $cb, $teamId)
    {
        /** @var PermissionRegistrar $reg */
        $reg = app(PermissionRegistrar::class);
        $prev = $reg->getPermissionsTeamId();
        try {
            $reg->setPermissionsTeamId($teamId);
            return $cb();
        } finally {
            $reg->setPermissionsTeamId($prev);
        }
    }
}
