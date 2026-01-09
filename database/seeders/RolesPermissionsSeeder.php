<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'rbac.view', 'rbac.assign',
            'employees.view',
            'org.view', 'org.create', 'org.update', 'org.delete',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit', 'recruitment.approve', 'recruitment.reject',
            'recruitment.external.view', 'recruitment.external.apply', 'recruitment.external.manage',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.approve', 'contract.sign',
            'training.dashboard.view', 'training.view',
            'training.management.view', 'training.management.approve',
            'reports.export',
            'applicant.data.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roleSuperadmin = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $roleSuperadmin->syncPermissions(Permission::all());

        $roleDhc = Role::firstOrCreate(['name' => 'DHC', 'guard_name' => 'web']);
        $roleDhc->syncPermissions([
            'users.view', 'rbac.view', 'rbac.assign',
            'employees.view',
            'org.view', 'org.create', 'org.update', 'org.delete',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.approve',
            'reports.export', 'recruitment.external.view', 'recruitment.external.manage',
            'training.dashboard.view', 'training.view',
            'training.management.view', 'training.management.approve',
        ]);

        $roleDirSdm = Role::firstOrCreate(['name' => 'Dir SDM', 'guard_name' => 'web']);
        $roleDirSdm->syncPermissions([
            'employees.view',
            'org.view', 'org.update',
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.approve',
            'training.view', 'reports.export',
        ]);

        $roleSdmUnit = Role::firstOrCreate(['name' => 'SDM Unit', 'guard_name' => 'web']);
        $roleSdmUnit->syncPermissions([
            'users.view',
            'employees.view',
            'org.view', 'org.create', 'org.update',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete',
            'reports.export', 'recruitment.external.view', 'recruitment.external.manage',
            'training.view', 'training.dashboard.view',
            'training.management.view',
        ]);

        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->syncPermissions([
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.approve', 'contract.sign', 
            'training.dashboard.view', 'training.view',
            'training.management.view', 'training.management.approve',
        ]);

        $roleAdminOps = Role::firstOrCreate(['name' => 'Admin Operasi Unit', 'guard_name' => 'web']);
        $roleAdminOps->syncPermissions([
            'employees.view',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit',
            'training.view',
        ]);

        $roleKepalaMP = Role::firstOrCreate(['name' => 'Kepala Proyek (MP)', 'guard_name' => 'web']);
        $roleKepalaMP->syncPermissions([
            'employees.view',
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'training.view',
        ]);

        $roleAvpHcOps = Role::firstOrCreate(['name' => 'AVP Human Capital Operation', 'guard_name' => 'web']);
        $roleAvpHcOps->syncPermissions([
            'users.view', 
            'employees.view',
            'org.view', 
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.approve',
            'training.view', 'training.dashboard.view',
            'training.management.view', 'training.management.approve',
        ]);

        $roleAvp = Role::firstOrCreate(['name' => 'AVP', 'guard_name' => 'web']);
        $roleAvp->syncPermissions([
            'training.view',
            'training.management.view', 'training.management.approve',
        ]);

        $roleDbsUnit = Role::firstOrCreate(['name' => 'DBS Unit', 'guard_name' => 'web']);
        $roleDbsUnit->syncPermissions([
            'training.view',
            'training.management.view',
        ]);

        $roleKaryawan = Role::firstOrCreate(['name' => 'Karyawan', 'guard_name' => 'web']);
        $roleKaryawan->syncPermissions([
            'contract.view', 'contract.sign',
            'training.view',
        ]);

        $rolePelamar = Role::firstOrCreate(['name' => 'Pelamar', 'guard_name' => 'web']);
        $rolePelamar->syncPermissions([
            'applicant.data.view', 
            'recruitment.external.apply',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}