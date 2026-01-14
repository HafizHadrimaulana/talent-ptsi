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

class UserController extends Controller
{
    public function index(Request $req)
    {
        $unitId = auth()->user()?->unit_id;
        $q = trim((string) $req->get('q', ''));

        $units = DB::table('units')->select('id', 'name')->orderBy('name', 'asc')->get();
        $roles = Role::query()->where('guard_name', 'web')
            ->where(function ($w) use ($unitId) {
                $w->whereNull('unit_id')->orWhere('unit_id', $unitId);
            })->orderBy('name', 'asc')->get(['id', 'name']);

        $empQ = DB::table('employees as e')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'e.unit_id')
            ->leftJoin('positions as pos', 'pos.id', '=', 'e.position_id')
            ->leftJoin('directorates as dir', 'dir.id', '=', 'e.directorate_id')
            ->leftJoin('users as us', 'us.employee_id', '=', 'e.employee_id');

        $this->applyStrictFilters($empQ);

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $empQ->where(function ($w) use ($like) {
                $w->where('e.employee_id', 'like', $like)
                    ->orWhere('e.id_sitms', 'like', $like)
                    ->orWhere('p.full_name', 'like', $like)
                    ->orWhere('u.name', 'like', $like)
                    ->orWhere('us.email', 'like', $like);
            });
        }

        $empRows = $empQ->selectRaw("
                e.id as employee_pk,
                e.employee_id,
                COALESCE(p.full_name, e.employee_id, CAST(e.id AS CHAR))     as full_name,
                COALESCE(pos.name, e.latest_jobs_title)                      as job_title,
                COALESCE(u.name,  e.latest_jobs_unit)                        as unit_name,
                u.id                                                         as employee_unit_id,
                COALESCE(e.email, p.email)                                   as employee_email,
                p.phone                                                      as phone,
                e.employee_status,
                e.talent_class_level,
                dir.name                                                     as directorate_name,
                e.home_base_city                                             as location_city,
                e.home_base_province                                         as location_province,
                e.profile_photo_url                                          as person_photo,
                COALESCE(e.company_name, 'PT Surveyor Indonesia')            as company_name,
                e.latest_jobs_start_date,
                us.id                                                        as user_id,
                us.email                                                     as user_email,
                us.name                                                      as user_name,
                us.unit_id                                                   as user_unit_id
            ")
            ->orderBy('full_name', 'asc')
            ->get();

        $userQ = DB::table('users as us')
            ->leftJoin('employees as e', 'e.employee_id', '=', 'us.employee_id')
            ->leftJoin('persons as p', 'p.id', '=', 'e.person_id')
            ->leftJoin('units as u', 'u.id', '=', 'us.unit_id')
            ->leftJoin('directorates as dir', 'dir.id', '=', 'e.directorate_id');

        $this->applyStrictFilters($userQ);

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $userQ->where(function ($w) use ($like) {
                $w->where('us.name', 'like', $like)
                    ->orWhere('us.email', 'like', $like);
            });
        }

        $userRows = $userQ->selectRaw("
                us.id          as user_id,
                us.name        as user_name,
                us.email       as user_email,
                us.unit_id     as user_unit_id,
                us.employee_id,
                COALESCE(p.full_name, us.name, us.email) as full_name,
                COALESCE(u.name, '-')           as unit_name,
                NULL                            as job_title,
                NULL                            as employee_pk,
                NULL                            as employee_email,
                NULL                            as phone,
                NULL                            as employee_status,
                NULL                            as employee_unit_id,
                e.talent_class_level,
                dir.name                        as directorate_name,
                e.home_base_city                as location_city,
                e.home_base_province            as location_province,
                e.profile_photo_url             as person_photo,
                'PT Surveyor Indonesia'         as company_name,
                e.latest_jobs_start_date
            ")
            ->get();

        $byKey = [];
        $merge = function($item) use (&$byKey) {
            $key = $item->employee_id ? ('E:' . $item->employee_id) : ('U:' . $item->user_id);
            if (!isset($byKey[$key])) {
                $byKey[$key] = (object) $item;
            } else {
                $exist = $byKey[$key];
                foreach ($item as $k => $v) {
                    if (property_exists($exist, $k) && empty($exist->$k) && !empty($v)) {
                        $exist->$k = $v;
                    }
                }
                $byKey[$key] = $exist;
            }
        };

        foreach ($empRows as $r) $merge($r);
        foreach ($userRows as $r) $merge($r);

        $rows = collect(array_values($byKey))
            ->sortBy(fn($r) => mb_strtolower($r->full_name ?? ''), SORT_NATURAL)
            ->values();

        $userIds = $rows->pluck('user_id')->filter()->unique()->values();
        $userRolesMap = [];
        if ($userIds->isNotEmpty()) {
            $pivot = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('mhr.model_type', User::class)
                ->whereIn('mhr.model_id', $userIds)
                ->where('r.guard_name', 'web')
                ->where(function ($w) use ($unitId) {
                    $w->whereNull('r.unit_id')->orWhere('r.unit_id', $unitId);
                })
                ->select('mhr.model_id', 'r.id')
                ->get()
                ->groupBy('model_id');

            foreach ($pivot as $uid => $list) {
                $userRolesMap[$uid] = $list->pluck('id')->values()->all();
            }
        }

        return view('admin.users.index', compact('rows', 'roles', 'units', 'userRolesMap', 'q'));
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

        $this->withTeamContext(function () use ($user, $data) {
            $assignables = $this->resolveAssignableRoles($data['roles'] ?? [], (int) $data['unit_id'], 'web');
            $user->syncRoles($assignables);
        }, $data['unit_id']);

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

        $this->withTeamContext(function () use ($user, $data) {
            if (array_key_exists('roles', $data)) {
                $assignables = $this->resolveAssignableRoles($data['roles'] ?? [], (int) $data['unit_id'], 'web');
                $user->syncRoles($assignables);
            }
        }, $data['unit_id']);

        return back()->with('ok', 'User updated successfully.');
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

        $disk = $disk ?: config('filesystems.default');
        try {
            $fs = Storage::disk($disk);
            if (method_exists($fs, 'url')) return (string) $fs->url($pathTrim);
        } catch (\Throwable $e) {}
        if ($disk === 'public') return asset('storage/' . $pathTrim);
        return $path;
    }
}