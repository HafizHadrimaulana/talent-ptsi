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

        $query = Employee::whereNotNull('unit_id')
            ->where('employee_status', '!=', 'Pelamar')
            ->where('employee_id', 'NOT LIKE', 'PL-%');

        $query->chunk(200, function ($employees) use ($password, $dhcUnitId) {
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

                $user = User::updateOrCreate(
                    ['employee_id' => $empId],
                    [
                        'name' => $emp->full_name ?? $empId,
                        'email' => $finalEmail,
                        'person_id' => $emp->person_id,
                        'unit_id' => $emp->unit_id,
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

                if ($isKepala && $dhcUnitId && $emp->unit_id == $dhcUnitId) {
                    app(PermissionRegistrar::class)->setPermissionsTeamId(0);
                    if (!$user->hasRole('DHC')) {
                        $user->assignRole('DHC');
                    }
                }

                $teamId = $user->unit_id ?? 0;
                app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

                // Hapus role lama yang tidak valid (biar role 'Kepala Unit' hilang dari AVP)
                $user->syncRoles($roles);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}