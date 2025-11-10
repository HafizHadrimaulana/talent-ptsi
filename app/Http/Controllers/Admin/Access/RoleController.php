<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\Role; // extend Spatie Role
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $unitId = auth()->user()?->unit_id;

        $roles = Role::query()
            ->where('guard_name','web')
            ->where(function($q) use ($unitId){
                $q->whereNull('unit_id')->orWhere('unit_id',$unitId);
            })
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        // Blade roles kamu juga menampilkan daftar permission per grup => sediakan groupedPerms
        $permissions = Permission::query()
            ->where('guard_name','web')
            ->orderBy('name')
            ->get();

        // group by prefix sebelum titik: users.create -> users
        $groupedPerms = $permissions->groupBy(function($p){
            $parts = explode('.', $p->name, 2);
            return $parts[0] ?? 'general';
        })->sortKeys();

        // -> resources/views/admin/roles/index.blade.php
        return view('admin.roles.index', compact('roles','groupedPerms'));
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'name' => ['required','string','max:100', Rule::unique('roles','name')],
        ]);

        $role = new Role();
        $role->name = $data['name'];
        $role->guard_name = 'web';
        $role->unit_id = auth()->user()?->unit_id; // scope ke unit pembuat
        $role->save();

        return back()->with('ok','Role created.');
    }

    public function update(Request $req, Role $role)
    {
        $data = $req->validate([
            'name' => ['required','string','max:100', Rule::unique('roles','name')->ignore($role->id)],
        ]);

        $role->name = $data['name'];
        $role->save();

        return back()->with('ok','Role updated.');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return back()->with('ok','Role deleted.');
    }
}
