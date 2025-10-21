<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Role;

class MigrateEmployeesToUsers extends Command
{
    protected $signature = 'employees:migrate-users {--role=Karyawan} {--dry}';
    protected $description = 'Migrate employees table to users (default password: "password")';

    public function handle(): int
    {
        $roleName = $this->option('role') ?: 'Karyawan';
        $role = Role::firstOrCreate(['name'=>$roleName,'guard_name'=>'web']);

        $count = 0;
        Employee::query()->chunk(500, function($chunk) use (&$count, $role) {
            foreach ($chunk as $e) {
                $employeeId = $e->employee_id ?: Str::upper(Str::ulid());
                $name = $e->full_name ?? $e->name ?? ('EMP-'.$employeeId);

                // guess email jika kosong
                $email = $e->email;
                if (!$email) {
                    $slug = Str::slug($name,'.');
                    $email = $employeeId.'@ptsi.local';
                }

                $user = User::firstOrNew([
                    'employee_id' => $employeeId,
                ]);

                if (!$user->exists && User::where('email',$email)->exists()) {
                    // hindari duplikat email
                    $email = $employeeId.'@ptsi.local';
                }

                $user->name = $name;
                $user->email = $email;
                $user->unit_id = $e->unit_id ?? null;
                $user->job_title = $e->job_title ?? $e->position_name ?? $e->position;
                if (!$user->password) {
                    $user->password = Hash::make('password'); // default
                }

                if (!$this->option('dry')) {
                    $user->save();
                    // mapping role GM/VP vs Cabang?
                    // aturan: DBS & Enabler => VP (role GM/VP Unit), Cabang => GM
                    // karena role cuma 1 "GM/VP Unit", assign itu jika jabatan mengandung GM/VP atau unit termasuk DBS/Enabler/Cabang
                    $roleToAssign = $role; // default dari option
                    $jab = strtolower((string)$user->job_title);
                    $unit = strtolower((string)($e->unit_name ?? ''));
                    if (str_contains($jab,'gm') || str_contains($jab,'vice') || str_contains($jab,'vp')
                        || str_contains($unit,'dbs') || str_contains($unit,'enabler') || str_contains($unit,'cabang')) {
                        $roleToAssign = Role::firstOrCreate(['name'=>'GM/VP Unit','guard_name'=>'web']);
                    }
                    $user->syncRoles([$roleToAssign->name]);
                }

                $count++;
            }
        });

        $this->info(($this->option('dry') ? '[DRY RUN] ' : '')."Processed: {$count} employees.");
        $this->info('Default password = "password"');
        return self::SUCCESS;
    }
}
