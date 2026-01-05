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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('roles')->where('name', 'VP DHC')->delete();

        DB::transaction(function () {
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

            DB::table('roles')
                ->where('name', 'GM/VP Unit')
                ->where('guard_name', 'web')
                ->update(['name' => 'Kepala Unit']);

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

            $finalRoles = ['Superadmin','DHC','Dir SDM','SDM Unit','Kepala Unit','Karyawan','Pelamar', 'AVP', 'DBS Unit'];
            $role = [];
            foreach ($finalRoles as $rn) {
                $role[$rn] = $keepRole($rn);
            }

            $roleGroups = DB::table('roles')
                ->select('name','guard_name', DB::raw('COUNT(*) as cnt'))
                ->groupBy('name','guard_name')
                ->having('cnt','>',1)
                ->get();

            foreach ($roleGroups as $g) {
                $role[$g->name] = $keepRole($g->name);
            }

            $perms = [
                'users.view','users.create','users.update','users.delete','rbac.view','rbac.assign',
                'employees.view',
                'org.view','org.create','org.update','org.delete',
                'recruitment.view','recruitment.create','recruitment.update','recruitment.submit','recruitment.approve','recruitment.reject',
                'recruitment.external.view','recruitment.external.apply','recruitment.external.manage',
                'contract.view','contract.create','contract.update','contract.delete','contract.approve','contract.sign',
                'training.view', 
                'training.management.view', 'training.management.approve',
                'reports.export',

                // Applicant 
                'applicant.data.view',
            ];

            foreach ($perms as $p) {
                Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            }

            $role['Superadmin']->syncPermissions(Permission::all());

            $role['DHC']->syncPermissions([
                'users.view','rbac.view','rbac.assign',
                'employees.view',
                'org.view','org.create','org.update','org.delete',
                'recruitment.view','recruitment.create','recruitment.update','recruitment.approve','recruitment.reject',
                'contract.view','contract.create','contract.update','contract.delete','contract.approve',
                'reports.export','recruitment.external.view','recruitment.external.manage',
                'training.view', 
                'training.management.view', 'training.management.approve', 
            ]);

            $role['Dir SDM']->syncPermissions([
                'employees.view',
                'org.view','org.update',
                'recruitment.view','recruitment.approve','recruitment.reject',
                'contract.view','contract.approve',
                'training.view','reports.export',
            ]);

            $role['SDM Unit']->syncPermissions([
                'users.view',
                'employees.view',
                'org.view','org.create','org.update',
                'recruitment.view','recruitment.create','recruitment.update','recruitment.submit',
                'contract.view','contract.create','contract.update','contract.delete',
                'reports.export','recruitment.external.view','recruitment.external.manage',
                'training.view',
                'training.management.view', 
            ]);

            $role['Kepala Unit']->syncPermissions([
                'employees.view',
                'org.view',
                'recruitment.view','recruitment.approve','recruitment.reject',
                'contract.view','contract.approve',
                'training.view',
                'training.management.view', 'training.management.approve',
            ]);

            $role['AVP']->syncPermissions([
                'training.view',
                'training.management.view', 'training.management.approve',
            ]);

            $role['DBS Unit']->syncPermissions([
                'training.view',
                'training.management.view',
            ]);

            $role['Karyawan']->syncPermissions([
                'employees.view','training.view',
            ]);


            $role['Pelamar']->syncPermissions([
                'applicant.data.view', 
                'recruitment.external.apply',
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}