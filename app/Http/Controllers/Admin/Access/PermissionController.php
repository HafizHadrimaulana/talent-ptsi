<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\Role; // unit-scoped role model
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::query()
            ->where('guard_name','web')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function update(Request $req, Permission $permission)
    {
        $data = $req->validate([
            'name'  => ['required','string','max:150'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $permission->name = $data['name'];
        $permission->save();

        // assign permission ke roles yang dicentang (unit-scoped list)
        // Ambil Role berdasarkan nama (di semua unit) lalu sync
        $permission->syncRoles($data['roles'] ?? []);

        // flush cache spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('ok','Permission updated.');
    }
}
