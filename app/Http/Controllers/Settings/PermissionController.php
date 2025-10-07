<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->paginate(50);
        return view('settings.permissions.index', compact('permissions'));
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name'=>"required|unique:permissions,name,{$permission->id}"
        ]);
        $permission->update($data);
        return back()->with('ok','Permission updated');
    }
}
