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
            // Users / RBAC
            'users.view','users.create','users.update','users.delete',
            'rbac.view','rbac.assign',

            // Directory / Employees
            'employees.view',

            // Recruitment
            'recruitment.view','recruitment.create','recruitment.update','recruitment.submit','recruitment.approve','recruitment.reject',

            // Contracts
            'contract.view','contract.create','contract.update','contract.approve','contract.sign',

            // Training
            'training.view',

            // Reports
            'reports.export',
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
            'users.view',
            'employees.view',
            'recruitment.view','recruitment.create','recruitment.update','recruitment.submit',
            'contract.view','contract.create','contract.update',
            'training.view',
            'reports.export',
        ]);

        Role::findByName('GM/VP Unit')->givePermissionTo([
            'employees.view',
            'recruitment.view',
            'contract.view','contract.approve',
            'training.view',
        ]);

        Role::findByName('Karyawan')->givePermissionTo([
            'employees.view',
            'training.view',
        ]);
    }
}
