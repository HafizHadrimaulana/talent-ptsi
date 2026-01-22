<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\EmployeeUserSeeder;

class SyncUsers extends Command
{
    protected $signature = 'users:sync';

    protected $description = 'Generate User Accounts & Roles from Employees data';

    public function handle(): int
    {
        $this->info('Starting User & Role synchronization...');
        
        $seeder = new EmployeeUserSeeder();
        $seeder->run();

        $this->info('User synchronization completed.');
        return self::SUCCESS;
    }
}