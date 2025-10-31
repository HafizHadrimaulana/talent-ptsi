<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->paginate(50);
        return view('admin.permissions.index', compact('permissions'));
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name'   => "required|string|unique:permissions,name,{$permission->id}",
            'roles'  => 'array',
            'roles.*'=> 'string'
        ]);

        $permission->update(['name' => $data['name']]);

        if ($request->has('roles')) {
            // roles[] berisi NAMA role
            $permission->syncRoles($data['roles']);
        }

        return back()->with('ok','Permission updated');
    }
}
