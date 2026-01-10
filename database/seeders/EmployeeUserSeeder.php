<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class EmployeeUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $password = Hash::make('password');

        $dhcUnitId = Unit::where('name', 'like', '%Human Capital%')
            ->orWhere('code', 'DHC')
            ->value('id');

        // Cache Valid Unit IDs untuk performa (biar gak query DB tiap row)
        $validUnitIds = Unit::pluck('id')->flip()->all();

        $query = Employee::whereNotNull('unit_id')
            ->where('employee_status', '!=', 'Pelamar')
            ->where('employee_id', 'NOT LIKE', 'PL-%');

        $query->chunk(200, function ($employees) use ($password, $dhcUnitId, $validUnitIds) {
            foreach ($employees as $emp) {
                $rawEmail = trim($emp->email);
                $empId = $emp->employee_id;

                if (empty($empId)) continue;

                if (empty($rawEmail) || $rawEmail === '0' || $rawEmail === '0@gmail.com' || !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                    $finalEmail = $empId . '@noemail.local';
                } else {
                    $isDuplicate = User::where('email', $rawEmail)
                        ->where('employee_id', '!=', $empId)
                        ->exists();
                    $finalEmail = $isDuplicate ? $empId . '.' . $rawEmail : $rawEmail;
                }

                // FAIL-SAFE: Cek apakah unit_id benar-benar ada di tabel units
                // Jika tidak ada (misal terhapus), set null agar tidak error Foreign Key
                $safeUnitId = isset($validUnitIds[$emp->unit_id]) ? $emp->unit_id : null;

                $user = User::updateOrCreate(
                    ['employee_id' => $empId],
                    [
                        'name' => $emp->full_name ?? $empId,
                        'email' => $finalEmail,
                        'person_id' => $emp->person_id,
                        'unit_id' => $safeUnitId, // Pakai ID yang sudah divalidasi
                        'password' => $password,
                    ]
                );

                $roles = ['Karyawan'];
                $title = strtolower($emp->latest_jobs_title ?? '');
                $isKepala = false;

                if (preg_match('/(avp|assistant\s*vice\s*president)/i', $title)) {
                    $roles[] = 'AVP';
                } elseif (preg_match('/(vp|vice\s*president|kepala\s*unit|kepala\s*satuan|general\s*manager|gm)/i', $title)) {
                    $roles[] = 'Kepala Unit';
                    $isKepala = true;
                }

                // Logic DHC hanya jika unit valid dan cocok
                if ($safeUnitId && $isKepala && $dhcUnitId && $safeUnitId == $dhcUnitId) {
                    app(PermissionRegistrar::class)->setPermissionsTeamId(0);
                    if (!$user->hasRole('DHC')) {
                        $user->assignRole('DHC');
                    }
                }

                $teamId = $safeUnitId ?? 0;
                app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

                // Sync roles (timpa role lama biar bersih)
                $user->syncRoles($roles);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}