<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Unit;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // daftar permission
        $perms = [
            // Users
            'users.view', 'users.create', 'users.update', 'users.delete',
            // Roles & perms
            'rbac.view','rbac.assign',
            // SPK/PKWT
            'contract.view','contract.create','contract.update','contract.approve',
            // Reports
            'reports.view','reports.export',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // roles
        $roles = [
            'Superadmin',
            'SDM Unit',
            'GM/VP Unit',
            'Karyawan',
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // contoh binding permission ke role (global)
        Role::findByName('Superadmin')->givePermissionTo(Permission::all());

        Role::findByName('SDM Unit')->givePermissionTo([
            'users.view','users.create','users.update',
            'rbac.view','rbac.assign',
            'contract.view','contract.create','contract.update',
            'reports.view','reports.export',
        ]);

        Role::findByName('GM/VP Unit')->givePermissionTo([
            'contract.view','contract.approve','reports.view'
        ]);

        Role::findByName('Karyawan')->givePermissionTo([
            'contract.view'
        ]);

        // set default team (unit) untuk context
        // Spatie akan membaca context team dari PermissionRegistrar::setPermissionsTeamId()
        // tapi untuk seed assign role per-unit, kita pakai manual di AdminUserSeeder.
    }
}
