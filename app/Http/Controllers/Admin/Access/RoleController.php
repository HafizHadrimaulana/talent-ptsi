<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->paginate(20);
        $permissions = Permission::orderBy('name')->get();
        $grouped = $permissions->groupBy(fn($p)=>explode('.',$p->name)[0]);
        return view('admin.roles.index', [
            'roles'=>$roles,
            'groupedPerms'=>$grouped,
            'allPerms'=>$permissions->pluck('name')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>'required|string|unique:roles,name',
            'permissions'=>'array'
        ]);
        $role = Role::create(['name'=>$data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        return back()->with('ok','Role created');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'=>"required|string|unique:roles,name,{$role->id}",
            'permissions'=>'array'
        ]);
        $role->update(['name'=>$data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        return back()->with('ok','Role updated');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return back()->with('ok','Role deleted');
    }
}
