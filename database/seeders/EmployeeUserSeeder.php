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

        $validUnitIds = Unit::pluck('id')->flip()->all();

        $query = Employee::with('person')
            ->whereNotNull('unit_id')
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

                $safeUnitId = isset($validUnitIds[$emp->unit_id]) ? $emp->unit_id : null;
                $fullName = $emp->person->full_name ?? $empId;

                $user = User::updateOrCreate(
                    ['employee_id' => $empId],
                    [
                        'name' => $fullName,
                        'email' => $finalEmail,
                        'person_id' => $emp->person_id,
                        'unit_id' => $safeUnitId,
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

                $shouldHaveDhc = ($safeUnitId && $isKepala && $dhcUnitId && $safeUnitId == $dhcUnitId);

                app(PermissionRegistrar::class)->setPermissionsTeamId(0);
                if ($shouldHaveDhc) {
                    if (!$user->hasRole('DHC')) {
                        $user->assignRole('DHC');
                    }
                } else {
                    if ($user->hasRole('DHC')) {
                        $user->removeRole('DHC');
                    }
                }

                $teamId = $safeUnitId ?? 0;
                app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

                $user->syncRoles($roles);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}