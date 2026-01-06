<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Kosongkan tabel terkait (Hanya untuk memastikan benar-benar bersih)
        Schema::disableForeignKeyConstraints();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        Schema::enableForeignKeyConstraints();

        // 3. Buat Permissions (Gabungan Lengkap)
        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'rbac.view', 'rbac.assign',
            'employees.view',
            'org.view', 'org.create', 'org.update', 'org.delete',
            'recruitment.view', 'recruitment.create', 'recruitment.update', 'recruitment.submit', 'recruitment.approve', 'recruitment.reject',
            'recruitment.external.view', 'recruitment.external.apply', 'recruitment.external.manage',
            'contract.view', 'contract.create', 'contract.update', 'contract.delete', 'contract.approve', 'contract.sign',
            'training.view',
            'training.dashboard.view', // Ditambahkan dari logika bawah
            'training.management.view', 'training.management.approve',
            'reports.export',
            
            // Applicant
            'applicant.data.view',
        ];

        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'web']);
        }

        // 4. Buat Roles dan Assign Permissions
        
        // --- Role: Superadmin ---
        $roleSuperadmin = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
        $roleSuperadmin->givePermissionTo(Permission::all());

        // --- Role: DHC ---
        $roleDhc = Role::create(['name' => 'DHC', 'guard_name' => 'web']);
        $roleDhc->givePermissionTo([
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
        $roleDirSdm = Role::create(['name' => 'Dir SDM', 'guard_name' => 'web']);
        $roleDirSdm->givePermissionTo([
            'employees.view',
            'org.view', 'org.update',
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.approve',
            'training.view', 'reports.export',
        ]);

        // --- Role: SDM Unit ---
        $roleSdmUnit = Role::create(['name' => 'SDM Unit', 'guard_name' => 'web']);
        $roleSdmUnit->givePermissionTo([
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
        $roleKepalaUnit = Role::create(['name' => 'Kepala Unit', 'guard_name' => 'web']);
        $roleKepalaUnit->givePermissionTo([
            'employees.view',
            'org.view',
            'recruitment.view', 'recruitment.approve', 'recruitment.reject',
            'contract.view', 'contract.approve',
            'training.view', 'training.dashboard.view',
            'training.management.view', 'training.management.approve',
        ]);

        // --- Role: AVP ---
        $roleAvp = Role::create(['name' => 'AVP', 'guard_name' => 'web']);
        $roleAvp->givePermissionTo([
            'training.view',
            'training.management.view', 'training.management.approve',
        ]);

        // --- Role: DBS Unit ---
        $roleDbsUnit = Role::create(['name' => 'DBS Unit', 'guard_name' => 'web']);
        $roleDbsUnit->givePermissionTo([
            'training.view',
            'training.management.view',
        ]);

        // --- Role: Karyawan (Ditambahkan dari logika bawah) ---
        $roleKaryawan = Role::create(['name' => 'Karyawan', 'guard_name' => 'web']);
        $roleKaryawan->givePermissionTo([
            'employees.view',
            'training.view',
        ]);

        // --- Role: Pelamar (Ditambahkan dari logika bawah) ---
        $rolePelamar = Role::create(['name' => 'Pelamar', 'guard_name' => 'web']);
        $rolePelamar->givePermissionTo([
            'applicant.data.view', 
            'recruitment.external.apply',
        ]);

        // 5. Final cache clear
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}