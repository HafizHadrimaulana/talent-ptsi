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
            ['code' => 'DBS_GNI', 'name' => 'Divisi Bisnis Strategis Government and Institution'],
            ['code' => 'DHC', 'name' => 'DHC (Enabler)'],
        ];

        foreach ($units as $u) {
            Unit::firstOrCreate(['code' => $u['code']], $u);
        }
    }
}
