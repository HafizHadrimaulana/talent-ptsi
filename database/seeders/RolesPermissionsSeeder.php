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
            'org.view', 'org.create', 'org.update', 'org.delete',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit', 'recruitment.approve', 'recruitment.reject',
            'recruitment.external.view', 'recruitment.external.apply', 'recruitment.external.manage',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.approve', 'contract.sign',
            'training.dashboard.view', 'training.view',
            'training.management.view', 'training.management.approve',
            'applicant.data.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'Superadmin' => Permission::all(),
            'DHC' => [
                'users.view', 'users.update',
                'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.approve', 'recruitment.reject',
                'contract.view', 'contract.create', 'contract.update', 'contract.delete',
                'recruitment.external.view', 'recruitment.external.manage',
                'training.dashboard.view', 'training.management.view', 'training.management.approve',
            ],
            'Dir SDM' => [
                'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            ],
            'SDM Unit' => [
                'users.view', 'users.update',
                'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit', 'recruitment.approve', 'recruitment.reject',
                'contract.view', 'contract.create', 'contract.update', 'contract.delete',
                'recruitment.external.view', 'recruitment.external.manage',
                'training.dashboard.view', 'training.management.view',
            ],
            'Kepala Unit' => [
                'recruitment.view', 'recruitment.approve', 'recruitment.reject',
                'contract.view', 'contract.approve', 'contract.sign',
                'training.dashboard.view', 'training.management.view', 'training.management.approve',
            ],
            'Admin Operasi Unit' => [
                'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit',
            ],
            'Kepala Proyek (MP)' => [
                'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            ],
            'AVP' => [
                'training.management.view', 'training.management.approve','recruitment.view', 'recruitment.approve', 'recruitment.reject',
            ],
            'Karyawan' => [
                'contract.view', 'contract.sign', 'training.view',
            ],
            'Pelamar' => [
                'applicant.data.view', 'recruitment.external.apply',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}