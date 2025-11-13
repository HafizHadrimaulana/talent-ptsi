<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache Spatie di awal
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Hapus role usang yang tidak dipakai lagi (idempotent)
        DB::table('roles')->where('name', 'VP DHC')->delete();

        DB::transaction(function () {

            // ============================================================
            // A) Dedup PERMISSIONS per (name, guard) → keep id terkecil
            //    Pindahkan pivots lalu hapus duplikat (idempotent)
            // ============================================================
            $permGroups = DB::table('permissions')
                ->select('name','guard_name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
                ->groupBy('name','guard_name')
                ->having('cnt','>',1)
                ->get();

            foreach ($permGroups as $g) {
                $dupIds = DB::table('permissions')
                    ->where('name', $g->name)
                    ->where('guard_name', $g->guard_name)
                    ->where('id', '<>', $g->keep_id)
                    ->pluck('id');

                if ($dupIds->isEmpty()) continue;

                DB::table('role_has_permissions')
                    ->whereIn('permission_id', $dupIds)
                    ->update(['permission_id' => $g->keep_id]);

                DB::table('permissions')->whereIn('id', $dupIds)->delete();
            }

            // ============================================================
            // B) Rename role lama "GM/VP Unit" → "Kepala Unit" (jika ada)
            // ============================================================
            DB::table('roles')
                ->where('name', 'GM/VP Unit')
                ->where('guard_name', 'web')
                ->update(['name' => 'Kepala Unit']);

            // ============================================================
            // C) Helper konsolidasi role per name (guard=web)
            //    - pilih keeper: global (unit_id NULL) kalau ada, else id terkecil
            //    - pindahkan seluruh pivots ke keeper, hapus sisanya
            //    - return instance Role keeper
            // ============================================================
            $keepRole = function(string $name): Role {
                $rows = DB::table('roles')
                    ->where('name', $name)
                    ->where('guard_name', 'web')
                    ->orderByRaw('CASE WHEN unit_id IS NULL THEN 0 ELSE 1 END')
                    ->orderBy('id')
                    ->get();

                if ($rows->isEmpty()) {
                    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                }

                $keepId  = $rows->first()->id;
                $dropIds = $rows->pluck('id')->filter(fn ($id) => $id !== $keepId)->values();

                if ($dropIds->isNotEmpty()) {
                    DB::table('model_has_roles')->whereIn('role_id', $dropIds)->update(['role_id' => $keepId]);
                    DB::table('role_has_permissions')->whereIn('role_id', $dropIds)->update(['role_id' => $keepId]);
                    DB::table('roles')->whereIn('id', $dropIds)->delete();
                }

                /** @var Role $role */
                $role = Role::query()->findOrFail($keepId);
                return $role;
            };

            // ============================================================
            // D) Pastikan role FINAL ada & terkonsolidasi (tanpa VP DHC)
            // ============================================================
            $finalRoles = ['Superadmin','DHC','Dir SDM','SDM Unit','Kepala Unit','Karyawan'];
            $role = [];
            foreach ($finalRoles as $rn) {
                $role[$rn] = $keepRole($rn);
            }

            // (Safety) Bila masih ada nama role yang dobel, konsolidasi ulang
            $roleGroups = DB::table('roles')
                ->select('name','guard_name', DB::raw('COUNT(*) as cnt'))
                ->groupBy('name','guard_name')
                ->having('cnt','>',1)
                ->get();

            foreach ($roleGroups as $g) {
                $role[$g->name] = $keepRole($g->name);
            }

            // ============================================================
            // E) Ensure PERMISSIONS exist (tambahkan org.*)
            // ============================================================
            $perms = [
                // Users / RBAC
                'users.view','users.create','users.update','users.delete',
                'rbac.view','rbac.assign',

                // Directory / Employees
                'employees.view',

                // Organization Master (Directorates & Units)
                'org.view','org.create','org.update','org.delete',

                // Recruitment
                'recruitment.view','recruitment.create','recruitment.update',
                'recruitment.submit','recruitment.approve','recruitment.reject',

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

            // ============================================================
            // F) Sync permission per role (idempotent)
            // ============================================================
            // Superadmin: semua
            $role['Superadmin']->syncPermissions(Permission::all());

            // DHC — checker/admin pusat
            $role['DHC']->syncPermissions([
                'users.view','rbac.view','rbac.assign',
                'employees.view',
                'org.view','org.create','org.update','org.delete',
                'recruitment.view','recruitment.create','recruitment.update','recruitment.approve','recruitment.reject',
                'contract.view','contract.create','contract.update','contract.approve',
                'training.view','reports.export',
            ]);

            // Dir SDM — approver final
            $role['Dir SDM']->syncPermissions([
                'employees.view',
                'org.view','org.update',
                'recruitment.view','recruitment.approve','recruitment.reject',
                'contract.view','contract.approve',
                'training.view','reports.export',
            ]);

            // SDM Unit — maker/submitter
            $role['SDM Unit']->syncPermissions([
                'users.view',
                'employees.view',
                'org.view','org.create','org.update',
                'recruitment.view','recruitment.create','recruitment.update','recruitment.submit',
                'contract.view','contract.create','contract.update',
                'training.view','reports.export',
            ]);

            // Kepala Unit — approver tahap 1 (unit-scoped)
            $role['Kepala Unit']->syncPermissions([
                'employees.view',
                'org.view',
                'recruitment.view','recruitment.approve','recruitment.reject',
                'contract.view','contract.approve',
                'training.view',
            ]);

            // Karyawan — minimal
            $role['Karyawan']->syncPermissions([
                'employees.view','training.view',
            ]);
        });

        // Reset cache Spatie di akhir
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
