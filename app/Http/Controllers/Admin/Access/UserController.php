<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $req)
    {
        if ($req->ajax()) {
            return $this->getDataTable($req);
        }

        $unitId = auth()->user()?->unit_id;
        $units = DB::table('units')->select('id', 'name')->orderBy('name', 'asc')->get();
        $roles = Role::query()->where('guard_name', 'web')
            ->where(function ($w) use ($unitId) {
                $w->whereNull('unit_id')->orWhere('unit_id', $unitId);
            })->orderBy('name', 'asc')->get(['id', 'name']);

        return view('admin.users.index', compact('roles', 'units'));
    }

    protected function getDataTable(Request $req)
    {
        $start = $req->get('start', 0);
        $length = $req->get('length', 10);
        $search = $req->get('search')['value'] ?? '';
        $order = $req->get('order')[0] ?? [];
        $colIdx = $order['column'] ?? 0;
        $dir = $order['dir'] ?? 'asc';
        
        $colMap = [
            0 => 'full_name',
            1 => 'job_title',
            2 => 'employee_status'
        ];
        $orderBy = $colMap[$colIdx] ?? 'full_name';

        $empQuery = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('directorates as dir', 'dir.id', '=', 'e.directorate_id')
            ->leftJoin('users as us', 'us.employee_id', '=', 'e.employee_id')
            ->selectRaw("
                e.id as employee_pk,
                e.employee_id,
                us.id as user_id,
                COALESCE(p.full_name, e.employee_id) as full_name,
                COALESCE(pos.name, e.latest_jobs_title) as job_title,
                COALESCE(u.name, e.latest_jobs_unit) as unit_name,
                COALESCE(e.email, p.email) as employee_email,
                us.email as user_email,
                us.name as user_name,
                us.unit_id as user_unit_id,
                e.employee_status,
                e.profile_photo_url as person_photo,
                p.phone,
                dir.name as directorate_name,
                e.home_base_city as location_city,
                e.talent_class_level,
                COALESCE(e.company_name, 'PT Surveyor Indonesia') as company_name,
                e.latest_jobs_start_date,
                'EMP' as type
            ");

        $this->applyStrictFilters($empQuery);

        $userQuery = DB::table('users as us')
            ->leftJoin('employees as e', 'e.employee_id', '=', 'us.employee_id')
            ->leftJoin('units as u', 'u.id', '=', 'us.unit_id')
            ->whereNull('us.employee_id')
            ->selectRaw("
                NULL as employee_pk,
                NULL as employee_id,
                us.id as user_id,
                us.name as full_name,
                NULL as job_title,
                COALESCE(u.name, '-') as unit_name,
                NULL as employee_email,
                us.email as user_email,
                us.name as user_name,
                us.unit_id as user_unit_id,
                NULL as employee_status,
                NULL as person_photo,
                NULL as phone,
                NULL as directorate_name,
                NULL as location_city,
                NULL as talent_class_level,
                'PT Surveyor Indonesia' as company_name,
                NULL as latest_jobs_start_date,
                'USR' as type
            ");

        $query = $empQuery->union($userQuery);
        
        $sql = $query->toSql();
        $wrapper = DB::table(DB::raw("({$sql}) as master_table"))
            ->mergeBindings($empQuery)
            ->mergeBindings($userQuery);

        if (!empty($search)) {
            $like = '%' . $search . '%';
            $wrapper->where(function($w) use ($like) {
                $w->where('full_name', 'like', $like)
                  ->orWhere('employee_id', 'like', $like)
                  ->orWhere('user_email', 'like', $like)
                  ->orWhere('unit_name', 'like', $like);
            });
        }

        $recordsFiltered = $wrapper->count();
        $recordsTotal = DB::table('employees')->count() + DB::table('users')->whereNull('employee_id')->count();

        $data = $wrapper->orderBy($orderBy, $dir)
            ->skip($start)
            ->take($length)
            ->get();

        $userIds = $data->pluck('user_id')->filter()->unique();
        $userRolesMap = [];
        if($userIds->isNotEmpty()){
            $userRolesMap = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('mhr.model_type', User::class)
                ->whereIn('mhr.model_id', $userIds)
                ->get()
                ->groupBy('model_id')
                ->map(fn($list) => $list->pluck('id')->all())
                ->all();
        }

        $data->transform(function($row) use ($userRolesMap) {
            $row->role_ids = isset($row->user_id) && isset($userRolesMap[$row->user_id]) 
                             ? $userRolesMap[$row->user_id] 
                             : [];
            return $row;
        });

        return response()->json([
            'draw' => intval($req->get('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $e = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('directorates as dir', 'dir.id', '=', 'e.directorate_id')
            ->where('e.id', $id)
            ->selectRaw("
                e.id, e.person_id, e.employee_id, e.id_sitms,
                COALESCE(p.full_name, e.employee_id) as full_name,
                COALESCE(pos.name, e.latest_jobs_title) as job_title,
                COALESCE(u.name,  e.latest_jobs_unit) as unit_name,
                COALESCE(e.email, p.email) as email,
                p.phone,
                e.profile_photo_url as person_photo,
                dir.name as directorate_name,
                e.home_base_city as location_city,
                'PT Surveyor Indonesia' as company_name,
                e.employee_status, 
                e.talent_class_level as talent,
                e.latest_jobs_start_date
            ")
            ->first();

        if (!$e) return response()->json(['error' => 'Not found'], 404);

        $port = function (array $cats) use ($e) {
            $qb = DB::table('portfolio_histories')
                ->select(['id', 'category', 'title', 'organization', 'start_date', 'end_date', 'description', 'meta'])
                ->whereIn(DB::raw('LOWER(category)'), array_map('strtolower', $cats));
            
            $qb->where(function ($w) use ($e) {
                if (!empty($e->person_id)) $w->orWhere('person_id', $e->person_id);
                if (!empty($e->employee_id)) $w->orWhere('employee_id', $e->employee_id);
            });

            return $qb->orderByRaw("COALESCE(end_date, start_date) desc")->get();
        };

        $documents = DB::table('documents as d')
            ->where(function ($w) use ($e) {
                if (!empty($e->person_id)) $w->orWhere('d.person_id', $e->person_id);
                if (!empty($e->employee_id)) $w->orWhere('d.employee_id', $e->employee_id);
            })
            ->selectRaw("
                d.doc_type, d.storage_disk, d.path,
                COALESCE(d.title, JSON_UNQUOTE(JSON_EXTRACT(d.meta, '$.document_title'))) as final_title,
                JSON_UNQUOTE(JSON_EXTRACT(d.meta, '$.document_duedate')) as meta_due_date
            ")
            ->orderByDesc('d.created_at')
            ->get()
            ->map(function ($d) {
                $d->url = $this->fsUrl($d->storage_disk, $d->path);
                return $d;
            });

        return response()->json([
            'brevet_list' => $port(['brevet']),
            'certifications' => $port(['certification']),
            'educations' => $port(['education']),
            'job_histories' => $port(['job']),
            'taskforces' => $port(['taskforce']),
            'assignments' => $port(['assignment']),
            'trainings' => $port(['training']),
            'documents' => $documents,
        ]);
    }

    protected function applyStrictFilters($query)
    {
        $query->where(function ($w) {
            $w->whereNull('e.company_name')
              ->orWhereRaw("TRIM(e.company_name) = ''")
              ->orWhereRaw("LOWER(e.company_name) LIKE '%surveyor indonesia%'")
              ->orWhereRaw("LOWER(e.company_name) = 'ptsi'");
        });

        $query->where(function ($w) {
            $w->where(function ($sub) {
                $sub->whereNull('e.employee_status')
                    ->orWhereRaw("TRIM(e.employee_status) = ''");
            })->orWhere(function ($sub) {
                $sub->whereRaw("LOWER(COALESCE(e.employee_status, '')) NOT LIKE '%alih%'")
                    ->whereRaw("LOWER(COALESCE(e.employee_status, '')) NOT LIKE '%source%'")
                    ->whereRaw("LOWER(COALESCE(e.employee_status, '')) NOT LIKE '%mitra%'")
                    ->whereRaw("LOWER(COALESCE(e.employee_status, '')) NOT LIKE '%magang%'");
            });
        });

        $query->where(function ($w) {
            $normUnit = "LOWER(REPLACE(TRIM(COALESCE(u.name, e.latest_jobs_unit, '')),'–','-'))";
            $w->whereRaw("$normUnit NOT LIKE '%kso%'")
              ->whereRaw("$normUnit NOT LIKE '%konsorsium%'")
              ->whereRaw("$normUnit NOT LIKE '%jo %'");
        });
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', 'min:8'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'roles' => ['array'],
        ]);

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password'] ?? 'password');
        $user->unit_id = $data['unit_id'];
        if (!empty($data['employee_id'])) $user->employee_id = $data['employee_id'];
        $user->save();

        $this->syncUserRolesSplitContext($user, $data['roles'] ?? [], (int) $data['unit_id']);

        return back()->with('ok', 'User created successfully.');
    }

    public function update(Request $req, User $user)
    {
        $data = $req->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'roles' => ['array'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) $user->password = Hash::make($data['password']);
        $user->unit_id = $data['unit_id'];
        $user->save();

        if (array_key_exists('roles', $data)) {
            $this->syncUserRolesSplitContext($user, $data['roles'] ?? [], (int) $data['unit_id']);
        }

        return back()->with('ok', 'User updated successfully.');
    }

    protected function syncUserRolesSplitContext(User $user, array $inputRoleIds, int $unitId)
    {
        $dhcRoleIds = Role::where('name', 'DHC')->pluck('id')->toArray();
        $shouldHaveDhc = !empty(array_intersect($inputRoleIds, $dhcRoleIds));
        $unitRoleIds = array_diff($inputRoleIds, $dhcRoleIds);

        $this->withTeamContext(function () use ($user, $shouldHaveDhc) {
            $user->unsetRelation('roles');

            if ($shouldHaveDhc) {
                if (!$user->hasRole('DHC')) $user->assignRole('DHC');
            } else {
                if ($user->hasRole('DHC')) $user->removeRole('DHC');
            }
        }, 0);

        $this->withTeamContext(function () use ($user, $unitRoleIds, $unitId) {
            $user->unsetRelation('roles');
            $assignables = $this->resolveAssignableRoles($unitRoleIds, $unitId, 'web');
            $user->syncRoles($assignables);
        }, $unitId);
    }

    public function roleOptions(Request $req) { return response()->json([]); }

    protected function withTeamContext(\Closure $cb, $teamId)
    {
        $reg = app(PermissionRegistrar::class);
        $prev = $reg->getPermissionsTeamId();
        try { $reg->setPermissionsTeamId($teamId); return $cb(); } finally { $reg->setPermissionsTeamId($prev); }
    }

    protected function resolveAssignableRoles(array $roleIds, int $unitId, string $guard)
    {
        $roleIds = array_values(array_unique(array_filter($roleIds, fn($v) => !is_null($v))));
        if (empty($roleIds)) return collect();

        $raw = Role::query()->whereIn('id', $roleIds)->get(['id', 'name', 'guard_name', 'unit_id']);
        $assign = collect();

        foreach ($raw as $src) {
            $target = Role::query()
                ->where('name', $src->name)->where('guard_name', $guard)
                ->where(function ($q) use ($unitId) { $q->whereNull('unit_id')->orWhere('unit_id', $unitId); })
                ->orderByRaw('CASE WHEN unit_id = ? THEN 0 ELSE 1 END', [$unitId])
                ->first();

            if (!$target) {
                $target = new Role();
                $target->name = $src->name; $target->guard_name = $guard; $target->unit_id = $unitId;
                $target->save();
            }
            $assign->push($target);
        }
        return $assign->unique('id');
    }

    private function fsUrl(?string $disk, ?string $path): string
    {
        if (!$path) return '';
        $pathTrim = ltrim((string) $path, '/');
        
        if (preg_match('~^https?://~i', $pathTrim)) return $pathTrim;

        if (strpos($pathTrim, 'uploads/') === 0) {
            return 'https://sitms.ptsi.co.id/' . $pathTrim;
        }

        $disk = $disk ?: config('filesystems.default');
        try {
            $fs = Storage::disk($disk);
            if (method_exists($fs, 'url')) return (string) $fs->url($pathTrim);
        } catch (\Throwable $e) {}
        if ($disk === 'public') return asset('storage/' . $pathTrim);
        return $path;
    }
}