<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        /** @var PermissionRegistrar $reg */
        $reg = app(PermissionRegistrar::class);
        $teamId = $reg->getPermissionsTeamId() ?: (auth()->user()?->unit_id);

        $q       = trim((string) $request->get('q', ''));
        $perPage = (int) $request->integer('per_page', 10) ?: 10;

        $rolesQuery = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($w) use ($teamId) {
                $w->whereNull('unit_id')->orWhere('unit_id', $teamId);
            })
            ->when($q !== '', function ($qq) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
                $qq->where('name', 'like', $like);
            })
            ->select('*')
            ->selectRaw('(
                SELECT COUNT(DISTINCT mhr.model_id)
                FROM model_has_roles AS mhr
                WHERE mhr.role_id = roles.id
                  AND mhr.model_type = ?
                  AND (mhr.unit_id = ? OR (mhr.unit_id IS NULL AND roles.unit_id IS NULL))
            ) AS users_count', [\App\Models\User::class, $teamId]); // <-- single backslash

        $roles = $rolesQuery->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        $allPerms = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $groupedPerms = $allPerms->groupBy(function ($p) {
            $parts = explode('.', $p->name, 2);
            return $parts[0];
        });

        $permByRole = [];
        foreach ($roles as $r) {
            $names = DB::table('permissions')
                ->join('role_has_permissions as rhp', 'permissions.id', '=', 'rhp.permission_id')
                ->where('rhp.role_id', $r->id)
                ->pluck('permissions.name');
            $permByRole[$r->id] = $names->values()->all();
        }

        return view('admin.roles.index', [
            'roles'        => $roles,
            'q'            => $q,
            'groupedPerms' => $groupedPerms,
            'permByRole'   => $permByRole,
        ]);
    }

    public function store(Request $request)
    {
        // Terima 'permissions' atau 'perms', boleh id (int) atau name (string)
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'unit_id'       => ['nullable', 'integer', 'exists:units,id'],
            'guard'         => ['nullable', 'string', 'in:web'],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['sometimes'],
            'perms'         => ['sometimes', 'array'],
            'perms.*'       => ['sometimes'],
        ]);

        /** @var PermissionRegistrar $reg */
        $reg = app(PermissionRegistrar::class);
        $teamId = $data['unit_id'] ?? ($reg->getPermissionsTeamId() ?: auth()->user()?->unit_id);

        $role = Role::firstOrCreate([
            'name'       => $data['name'],
            'guard_name' => $data['guard'] ?? 'web',
            'unit_id'    => $teamId,
        ]);

        $permModels = $this->resolvePermissions($request);
        $role->syncPermissions($permModels);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', 'Role saved.');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('roles', 'name')
                    ->ignore($role->id)
                    ->where(fn($q) => $q->where('guard_name', $role->guard_name)->where('unit_id', $role->unit_id)),
            ],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['sometimes'],
            'perms'         => ['sometimes', 'array'],
            'perms.*'       => ['sometimes'],
        ]);

        $role->name = $data['name'];
        $role->save();

        $permModels = $this->resolvePermissions($request);
        $role->syncPermissions($permModels);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok', 'Role updated.');
    }

    /**
     * Resolve permission input (id atau name) ke koleksi Permission
     */
    protected function resolvePermissions(Request $request)
    {
        $keys = $request->input('permissions', $request->input('perms', []));

        if (empty($keys)) {
            return collect();
        }

        // Pisahkan mana numeric (id) dan mana string (name)
        $ids   = [];
        $names = [];
        foreach ($keys as $k) {
            if (is_numeric($k)) {
                $ids[] = (int) $k;
            } else {
                $names[] = (string) $k;
            }
        }

        return Permission::query()
            ->where('guard_name', 'web')
            ->when(!empty($ids), fn($q) => $q->orWhereIn('id', $ids))
            ->when(!empty($names), fn($q) => $q->orWhereIn('name', $names))
            ->get();
    }
}
