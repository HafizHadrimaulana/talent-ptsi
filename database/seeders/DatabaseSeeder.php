<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitsSeeder::class,
            RolesPermissionsSeeder::class,
            ContractTemplateSeeder::class,
            PrincipalApprovalTemplateSeeder::class,
            AdminUserSeeder::class,
            EmployeeUserSeeder::class,
            QuestionEvaluationSeeder::class
        ]);
    }
}