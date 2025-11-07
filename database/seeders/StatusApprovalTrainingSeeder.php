<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusApprovalTrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('status_approval_training')->insert([
            ['id' => 1, 'status_approval' => 'Pending'],
            ['id' => 2, 'status_approval' => 'Menunggu Persetujuan'],
            ['id' => 3, 'status_approval' => 'Menunggu Persetujuan Akhir'],
            ['id' => 4, 'status_approval' => 'Diterima'],
            ['id' => 5, 'status_approval' => 'Ditolak'],
            ['id' => 6, 'status_approval' => 'Menunggu Persetujuan DBS'],
        ]);
    }
}
