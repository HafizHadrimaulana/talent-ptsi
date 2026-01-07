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
        // 1. Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Daftar Permissions
        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'rbac.view', 'rbac.assign',
            'employees.view',
            'org.view', 'org.create', 'org.update', 'org.delete',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit', 'recruitment.approve', 'recruitment.reject',
            'recruitment.external.view', 'recruitment.external.apply', 'recruitment.external.manage',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.approve', 'contract.sign',
            'training.view',
            'training.dashboard.view',
            'training.management.view', 'training.management.approve',
            'reports.export',
            
            // Applicant
            'applicant.data.view',
        ];

        // 3. Buat Permissions (Pakai firstOrCreate agar tidak error duplicate entry)
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 4. Buat/Cari Roles dan Sync Permissions
        // --- Role: Superadmin ---
        $roleSuperadmin = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
        $roleSuperadmin->syncPermissions(Permission::all());

        // --- Role: DHC ---
        $roleDhc = Role::firstOrCreate(['name' => 'DHC', 'guard_name' => 'web']);
        $roleDhc->syncPermissions([
            'users.view', 'rbac.view', 'rbac.assign',
            'employees.view',
            'org.view', 'org.create', 'org.update', 'org.delete',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.approve',
            'reports.export', 'recruitment.external.view', 'recruitment.external.manage',
            'training.view', 'training.dashboard.view',
            'training.management.view', 'training.management.approve',
        ]);

        // --- Role: Dir SDM ---
        $roleDirSdm = Role::firstOrCreate(['name' => 'Dir SDM', 'guard_name' => 'web']);
        $roleDirSdm->syncPermissions([
            'employees.view',
            'org.view', 'org.update',
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.approve',
            'training.view', 'reports.export',
        ]);

        // --- Role: SDM Unit ---
        $roleSdmUnit = Role::firstOrCreate(['name' => 'SDM Unit', 'guard_name' => 'web']);
        $roleSdmUnit->syncPermissions([
            'users.view',
            'employees.view',
            'org.view', 'org.create', 'org.update',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete',
            'reports.export', 'recruitment.external.view', 'recruitment.external.manage',
            'training.view', 'training.dashboard.view',
            'training.management.view',
        ]);

        // --- Role: Kepala Unit ---
        $roleKepalaUnit = Role::firstOrCreate(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->syncPermissions([
            'employees.view',
            'org.view',
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.approve',
            'training.view', 'training.dashboard.view',
            'training.management.view', 'training.management.approve',
        ]);

        // --- Role: AVP ---
        $roleAvp = Role::firstOrCreate(['name' => 'AVP', 'guard_name' => 'web']);
        $roleAvp->syncPermissions([
            'training.view',
            'training.management.view', 'training.management.approve',
        ]);

        // --- Role: DBS Unit ---
        $roleDbsUnit = Role::firstOrCreate(['name' => 'DBS Unit', 'guard_name' => 'web']);
        $roleDbsUnit->syncPermissions([
            'training.view',
            'training.management.view',
        ]);

        // --- Role: Karyawan ---
        $roleKaryawan = Role::firstOrCreate(['name' => 'Karyawan', 'guard_name' => 'web']);
        $roleKaryawan->syncPermissions([
            'employees.view',
            'training.view',
        ]);

        // --- Role: Pelamar ---
        $rolePelamar = Role::firstOrCreate(['name' => 'Pelamar', 'guard_name' => 'web']);
        $rolePelamar->syncPermissions([
            'applicant.data.view', 
            'recruitment.external.apply',
        ]);

        // 5. Final cache clear
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}