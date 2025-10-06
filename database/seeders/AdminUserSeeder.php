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
        $unit = Unit::where('code','SIHO')->first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@ptsi.co.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'unit_id' => optional($unit)->id,
            ]
        );

        // set team context ke unit admin
        app(PermissionRegistrar::class)->setPermissionsTeamId(optional($unit)->id);

        $admin->assignRole('Superadmin');
    }
}
