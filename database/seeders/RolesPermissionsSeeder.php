<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            // Users
            'users.view', 'users.create', 'users.update', 'users.delete',
            // Roles & perms
            'rbac.view','rbac.assign',
            // Rekrutmen/Contract
            'recruitment.view',
            'contract.view','contract.create','contract.update','contract.approve','contract.sign',
            // Pelatihan
            'training.view',
            // Reports
            'reports.view','reports.export',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $roles = ['Superadmin','SDM Unit','GM/VP Unit','Karyawan'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        Role::findByName('Superadmin')->givePermissionTo(Permission::all());

        Role::findByName('SDM Unit')->givePermissionTo([
            'users.view','users.create','users.update',
            'rbac.view','rbac.assign',
            'recruitment.view',
            'contract.view','contract.create','contract.update',
            'training.view',
            'reports.view','reports.export',
        ]);

        Role::findByName('GM/VP Unit')->givePermissionTo([
            'recruitment.view','contract.view','contract.approve',
            'training.view','reports.view'
        ]);

        Role::findByName('Karyawan')->givePermissionTo([
            'recruitment.view','contract.view','training.view'
        ]);
    }
}
