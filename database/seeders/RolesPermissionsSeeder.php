<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // === DAFTAR PERMISSION (DITAMBAH BLOK RECRUITMENT.*) ===
        $perms = [
            // Users
            'users.view', 'users.create', 'users.update', 'users.delete',

            // Roles & Permissions
            'rbac.view','rbac.assign',

            // Rekrutmen (tambah biar bisa diatur di Manajemen Akses)
            'recruitment.view',
            'recruitment.create',
            'recruitment.update',
            'recruitment.submit',
            'recruitment.approve',
            'recruitment.reject',

            // Kontrak
            'contract.view','contract.create','contract.update','contract.approve','contract.sign',

            // Pelatihan
            'training.view',

            // Reports
            'reports.view','reports.export',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // === ROLES (biarkan sesuai strukturmu) ===
        $roles = ['Superadmin','SDM Unit','GM/VP Unit','Karyawan'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // === ASSIGN DEFAULT (boleh kamu ubah di UI setelahnya) ===
        // Superadmin: semua permission
        Role::findByName('Superadmin')->givePermissionTo(Permission::all());

        // SDM Unit: akses operasional rekrutmen (view/create/update/submit) + kontrak operasional
        Role::findByName('SDM Unit')->givePermissionTo([
            'users.view','users.create','users.update',
            'rbac.view','rbac.assign',

            'recruitment.view','recruitment.create','recruitment.update','recruitment.submit',

            'contract.view','contract.create','contract.update',

            'training.view',
            'reports.view','reports.export',
        ]);

        // GM/VP Unit: lihat & approve kontrak, lihat rekrutmen
        Role::findByName('GM/VP Unit')->givePermissionTo([
            'recruitment.view',
            'contract.view','contract.approve',
            'training.view','reports.view',
        ]);

        // Karyawan: lihat saja
        Role::findByName('Karyawan')->givePermissionTo([
            'recruitment.view','contract.view','training.view'
        ]);
    }
}
