<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role; // extends Spatie\Permission\Models\Role (kolom unit_id ada)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function index(Request $req)
    {
        $unitId = auth()->user()?->unit_id;
        $q = trim((string) $req->get('q', ''));

        // Units for selector (label=name, value=id)
        $units = DB::table('units')->select('id','name')->orderBy('name','asc')->get();

        // Role list untuk selector (yang terlihat oleh viewer: global/null atau unit viewer)
        $roles = Role::query()
            ->where('guard_name','web')
            ->where(function($w) use ($unitId){
                $w->whereNull('unit_id')->orWhere('unit_id',$unitId);
            })
            ->orderBy('name','asc')
            ->get(['id','name']);

        // ================================
        // A) DATA KARYAWAN (employees join users)
        // ================================
        $empQ = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('users as us', 'us.employee_id', '=', 'e.employee_id')
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
            });

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $empQ->where(function ($w) use ($like) {
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
                  ->orWhere('p.email', 'like', $like)
                  ->orWhere('us.email', 'like', $like)
                  ->orWhere('us.name', 'like', $like);
            });
        }

        $empRows = $empQ->selectRaw("
                e.id as employee_pk,
                e.employee_id,
                e.id_sitms,
                COALESCE(p.full_name, e.employee_id, CAST(e.id AS CHAR))  as full_name,
                COALESCE(pos.name, e.latest_jobs_title)                    as job_title,
                COALESCE(u.name,  e.latest_jobs_unit)                     as unit_name,
                u.id                                                      as employee_unit_id,
                COALESCE(e.email, p.email)                                as employee_email,
                e.employee_status                                         as employee_status,
                us.id                                                     as user_id,
                us.email                                                  as user_email,
                us.name                                                   as user_name,
                us.unit_id                                                as user_unit_id
            ")
            ->orderBy('full_name','asc')
            ->get();

        // ================================
        // B) DATA USER STANDALONE (users tanpa employee match)
        // ================================
        $userQ = DB::table('users as us')
            ->leftJoin('employees as e', 'e.employee_id', '=', 'us.employee_id')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'us.unit_id');

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $userQ->where(function($w) use ($like) {
                $w->where('us.name','like',$like)
                  ->orWhere('us.email','like',$like)
                  ->orWhere('us.employee_id','like',$like)
                  ->orWhere('u.name','like',$like);
            });
        }

        $userRows = $userQ->selectRaw("
                us.id    as user_id,
                us.name  as user_name,
                us.email as user_email,
                us.unit_id as user_unit_id,
                us.employee_id as user_employee_id,
                COALESCE(p.full_name, us.name, us.email, CONCAT('User#',us.id)) as full_name,
                COALESCE(u.name, '-')   as unit_name,
                NULL as job_title,
                NULL as employee_pk,
                NULL as id_sitms,
                NULL as employee_email,
                NULL as employee_status,
                NULL as employee_unit_id
            ")
            ->get();

        // ================================
        // C) MERGE TANPA DUPLIKAT
        // ================================
        $byKey = [];

        foreach ($empRows as $r) {
            $key = $r->employee_id ? ('E:'.$r->employee_id) : ('U:'.$r->user_id);
            if (!isset($byKey[$key])) $byKey[$key] = $r;
            else $byKey[$key] = (object) array_merge((array)$byKey[$key], (array)$r);
        }

        foreach ($userRows as $u) {
            $key = $u->user_employee_id ? ('E:'.$u->user_employee_id) : ('U:'.$u->user_id);
            if (!isset($byKey[$key])) {
                $row = (object)[
                    'employee_pk'      => null,
                    'employee_id'      => $u->user_employee_id,
                    'id_sitms'         => null,
                    'full_name'        => $u->full_name,
                    'job_title'        => $u->job_title,
                    'unit_name'        => $u->unit_name,
                    'employee_unit_id' => null,
                    'employee_email'   => null,
                    'employee_status'  => null,
                    'user_id'          => $u->user_id,
                    'user_email'       => $u->user_email,
                    'user_name'        => $u->user_name,
                    'user_unit_id'     => $u->user_unit_id,
                ];
                $byKey[$key] = $row;
            } else {
                $row = $byKey[$key];
                if (!$row->user_id)       $row->user_id       = $u->user_id;
                if (!$row->user_email)    $row->user_email    = $u->user_email;
                if (!$row->user_name)     $row->user_name     = $u->user_name;
                if (!$row->user_unit_id)  $row->user_unit_id  = $u->user_unit_id;
                $byKey[$key] = $row;
            }
        }

        // Urutkan by full_name asc
        $rows = collect(array_values($byKey))
            ->sortBy(fn($r) => mb_strtolower($r->full_name ?? ''), SORT_NATURAL)
            ->values();

        // Prefetch roles per user (scoped viewer unit)
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
            'units' => $units,
            'userRolesMap' => $userRolesMap,
            'q'     => $q,
        ]);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name'        => ['required','string','max:255'],
            'email'       => ['required','email','max:255', Rule::unique('users','email')],
            'password'    => ['nullable','string','min:8'],
            'employee_id' => ['nullable','string','max:255'],
            'unit_id'     => ['required','integer','exists:units,id'],
            'roles'       => ['array'],
            'roles.*'     => ['integer','exists:roles,id'],
        ]);

        $user = new User();
        $user->name       = $data['name'];
        $user->email      = $data['email'];
        $user->password   = Hash::make($data['password'] ?? 'password');
        $user->unit_id    = $data['unit_id'];
        if (!empty($data['employee_id'])) {
            $user->employee_id = $data['employee_id'];
        }
        $user->save();

        // Assign roles — resolve dinamis by NAME -> guard=web & unit target; auto-create jika perlu
        $this->withTeamContext(function() use ($user, $data){
            $assignables = $this->resolveAssignableRoles($data['roles'] ?? [], (int)$data['unit_id'], 'web');
            $user->syncRoles($assignables);
        }, $data['unit_id']);

        return back()->with('ok','Account created (default password: "password").');
    }

    public function update(Request $req, User $user)
    {
        $data = $req->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:8'],
            'unit_id'  => ['required','integer','exists:units,id'],
            'roles'    => ['array'],
            'roles.*'  => ['integer','exists:roles,id'],
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->unit_id = $data['unit_id'];
        $user->save();

        $this->withTeamContext(function() use ($user, $data){
            if (array_key_exists('roles', $data)) {
                $assignables = $this->resolveAssignableRoles($data['roles'] ?? [], (int)$data['unit_id'], 'web');
                $user->syncRoles($assignables);
            }
        }, $data['unit_id']);

        return back()->with('ok','Account updated.');
    }

    // AJAX roles (optional ?unit_id=...)
    public function roleOptions(Request $req)
    {
        $unitId = $req->integer('unit_id') ?: (auth()->user()?->unit_id);
        $roles = Role::query()
            ->where('guard_name','web')
            ->where(function($q) use ($unitId){
                $q->whereNull('unit_id')->orWhere('unit_id', $unitId);
            })
            ->orderBy('name','asc')
            ->get(['id','name']);

        return response()->json($roles->map(fn($r)=>['id'=>$r->id,'name'=>$r->name]));
    }

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

    /**
     * Resolve daftar role ID dari form menjadi KOLEKSI Role yang:
     * - guard_name = $guard ('web')
     * - unit_id = ($unitId) jika ada variannya; jika belum ada → auto-create per-unit
     * - fallback: jika varian per-unit tidak ada, boleh pakai global (unit_id NULL)
     *
     * Tujuan: role baru "DHC" yang kebuat dengan guard/unit tidak pas tetap bisa di-assign.
     */
    protected function resolveAssignableRoles(array $roleIds, int $unitId, string $guard)
    {
        $roleIds = array_values(array_unique(array_filter($roleIds, fn($v)=>!is_null($v))));
        if (empty($roleIds)) return collect();

        // Ambil role asal (apa pun guard/unit)
        $raw = Role::query()->whereIn('id', $roleIds)->get(['id','name','guard_name','unit_id']);

        $assign = collect();

        foreach ($raw as $src) {
            $name = $src->name;

            // Prefer per-unit & guard sesuai
            $target = Role::query()
                ->where('name', $name)
                ->where('guard_name', $guard)
                ->where(function($q) use ($unitId){
                    $q->whereNull('unit_id')->orWhere('unit_id',$unitId);
                })
                ->orderByRaw('CASE WHEN unit_id = ? THEN 0 ELSE 1 END', [$unitId])
                ->first();

            if (!$target) {
                // Belum ada varian yang sesuai → buat per-unit dengan guard=web
                $target = new Role();
                $target->name = $name;
                $target->guard_name = $guard;
                $target->unit_id = $unitId; // per-unit
                $target->save();
            }

            $assign->push($target);
        }

        // Unikkan by (name, guard, unit)
        $assign = $assign->unique(fn($r)=>$r->name.'|'.$r->guard_name.'|'.($r->unit_id ?? 'null'))->values();

        return $assign;
    }
}
