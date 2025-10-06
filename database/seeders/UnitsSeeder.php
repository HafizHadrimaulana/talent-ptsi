<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'SIHO', 'name' => 'SI Head Office'],
            ['code' => 'DBS1', 'name' => 'Divisi Bisnis Strategis 1'],
            ['code' => 'ENBL', 'name' => 'Enabler'],
        ];

        foreach ($units as $u) {
            Unit::firstOrCreate(['code' => $u['code']], $u);
        }
    }
}
