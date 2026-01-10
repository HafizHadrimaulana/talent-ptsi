<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $password = Hash::make('password');

        $siho = Unit::where('code', 'SIHO')->first();
        if ($siho) {
            $superAdmin = User::updateOrCreate(
                ['email' => 'admin@ptsi.co.id'],
                [
                    'name' => 'Super Admin',
                    'password' => $password,
                    'unit_id' => $siho->id,
                ]
            );
            app(PermissionRegistrar::class)->setPermissionsTeamId($siho->id);
            $superAdmin->assignRole('Superadmin');
        }

        $units = Unit::all();
        foreach ($units as $unit) {
            if ($unit->code === 'SIHO') continue;
            if (str_contains(strtolower($unit->name), 'pelamar')) continue;

            $cleanCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $unit->code ?? 'unit' . $unit->id));
            $email = "admin.{$cleanCode}@ptsi.co.id";

            $adminUnit = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => 'Admin ' . ($unit->code ?? $unit->name),
                    'password' => $password,
                    'employee_id' => 'ADM-' . $unit->id,
                    'unit_id' => $unit->id,
                ]
            );

            app(PermissionRegistrar::class)->setPermissionsTeamId($unit->id);
            $adminUnit->assignRole('SDM Unit');
        }
    }
}