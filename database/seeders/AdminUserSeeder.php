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
        // 1) Pastikan unit Head Office (SIHO) ADA
        //    - Coba cari berdasarkan CODE dulu
        //    - Kalau belum ada, coba cari berdasarkan NAME
        //    - Kalau tetap nggak ada, buat baru
        $unit = Unit::where('code', 'SIHO')->first();

        if (! $unit) {
            $unit = Unit::where('name', 'SI Head Office')->first();
        }

        if (! $unit) {
            // units: id, code, name, directorate_id, timestamps
            $unit = Unit::create([
                'code'           => 'SIHO',
                'name'           => 'SI Head Office',
                'directorate_id' => null,
            ]);
        }

        // 2) Buat / update user admin
        //    - Kalau sudah ada, pastikan unit_id diisi (bukan NULL)
        $admin = User::updateOrCreate(
            ['email' => 'admin@ptsi.co.id'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'unit_id'  => $unit->id,
            ]
        );

        // 3) Set context team ke unit admin (SIHO) sebelum assignRole
        /** @var \Spatie\Permission\PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($unit->id);

        // 4) Assign role Superadmin di team SIHO
        //    Kalau role "Superadmin" belum ada, pastikan RolesPermissionsSeeder sudah jalan duluan.
        $admin->assignRole('Superadmin');
    }
}
