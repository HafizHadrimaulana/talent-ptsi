<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        // Blade permissions kamu memakai variabel $permissions (bukan $perms)
        $permissions = Permission::query()
            ->where('guard_name','web')
            ->orderBy('name')
            ->paginate(20);

        // -> resources/views/admin/permissions/index.blade.php
        return view('admin.permissions.index', compact('permissions'));
    }

    public function update(Request $req, Permission $permission)
    {
        $data = $req->validate([
            'name' => ['required','string','max:150'],
        ]);

        $permission->name = $data['name'];
        $permission->save();

        return back()->with('ok','Permission updated.');
    }
}
