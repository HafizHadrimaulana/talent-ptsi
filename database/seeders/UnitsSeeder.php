<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $data = [
            1 => ['code' => 'SIHO', 'name' => 'SI Head Office', 'category' => null],
            2 => ['code' => 'DBSOGRE', 'name' => 'Divisi Bisnis Strategis Oil, Gas and Renewable Energy', 'category' => 'operasi'],
            3 => ['code' => 'DBSCNM', 'name' => 'Divisi Bisnis Strategis Coal and Mineral', 'category' => 'operasi'],
            4 => ['code' => 'DBSGNI', 'name' => 'Divisi Bisnis Strategis Government and Institution', 'category' => 'operasi'],
            5 => ['code' => 'DBSINS', 'name' => 'Divisi Bisnis Strategis Industrial Services', 'category' => 'operasi'],
            6 => ['code' => 'DBSINT', 'name' => 'Divisi Bisnis Strategis Infrastructure and Transportation', 'category' => 'operasi'],
            7 => ['code' => 'DBSSNE', 'name' => 'Divisi Bisnis Strategis Sustainability and Environment', 'category' => 'operasi'],
            8 => ['code' => 'SIJAK', 'name' => 'Cabang Jakarta', 'category' => 'cabang'],
            9 => ['code' => 'SISUB', 'name' => 'Cabang Surabaya', 'category' => 'cabang'],
            10 => ['code' => 'SIMAK', 'name' => 'Cabang Makassar', 'category' => 'cabang'],
            11 => ['code' => 'SIBAT', 'name' => 'Cabang Batam', 'category' => 'cabang'],
            12 => ['code' => 'SIBPP', 'name' => 'Cabang Balikpapan', 'category' => 'cabang'],
            13 => ['code' => 'SIMED', 'name' => 'Cabang Medan', 'category' => 'cabang'],
            14 => ['code' => 'SIPAL', 'name' => 'Cabang Palembang', 'category' => 'cabang'],
            15 => ['code' => 'SIPKU', 'name' => 'Cabang Pekanbaru', 'category' => 'cabang'],
            16 => ['code' => 'SISMA', 'name' => 'Cabang Semarang', 'category' => 'cabang'],
            17 => ['code' => 'SISG', 'name' => 'Cabang Singapura', 'category' => 'cabang'],
            26 => ['code' => 'SP', 'name' => 'Sekretariat Perusahaan', 'category' => 'enabler'],
            27 => ['code' => 'SPI', 'name' => 'Satuan Pengawasan Intern', 'category' => 'enabler'],
            28 => ['code' => 'DRP2B', 'name' => 'Divisi Riset, Pemasaran dan Pengembangan Bisnis', 'category' => 'enabler'],
            29 => ['code' => 'DOP', 'name' => 'Divisi Operasi', 'category' => 'enabler'],
            30 => ['code' => 'DKA', 'name' => 'Divisi Keuangan dan Akuntansi', 'category' => 'enabler'],
            31 => ['code' => 'DPKMR', 'name' => 'Divisi Perencanaan Korporat dan Manajemen Risiko', 'category' => 'enabler'],
            32 => ['code' => 'DMA', 'name' => 'Divisi Manajemen Aset', 'category' => 'enabler'],
            33 => ['code' => 'DHC', 'name' => 'Divisi Human Capital', 'category' => 'enabler'],
            34 => ['code' => 'DTI', 'name' => 'Divisi Teknologi Informasi', 'category' => 'enabler'],
            35 => ['code' => 'STO', 'name' => 'Strategic Transformation Office', 'category' => 'enabler'],
            36 => ['code' => 'UTJSL', 'name' => 'Unit Tanggung Jawab Sosial dan Lingkungan', 'category' => 'enabler'],
        ];

        $now = now();
        foreach ($data as $id => $val) {
            DB::table('units')->updateOrInsert(
                ['id' => $id],
                [
                    'code' => $val['code'],
                    'name' => $val['name'],
                    'category' => $val['category'],
                    'updated_at' => $now,
                ]
            );
        }

        Schema::enableForeignKeyConstraints();
    }
}