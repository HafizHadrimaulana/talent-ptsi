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
        DB::table('units')->truncate();
        Schema::enableForeignKeyConstraints();

        $data = [
            1 => ['code' => 'SIHO', 'name' => 'SI Head Office'],
            2 => ['code' => 'DBSOGRE', 'name' => 'Divisi Bisnis Strategis Oil, Gas and Renewable Energy'],
            3 => ['code' => 'DBSCNM', 'name' => 'Divisi Bisnis Strategis Coal and Mineral'],
            4 => ['code' => 'DBSGNI', 'name' => 'Divisi Bisnis Strategis Government and Institution'],
            5 => ['code' => 'DBSINS', 'name' => 'Divisi Bisnis Strategis Industrial Services'],
            6 => ['code' => 'DBSINT', 'name' => 'Divisi Bisnis Strategis Infrastructure and Transportation'],
            7 => ['code' => 'DBSSNE', 'name' => 'Divisi Bisnis Strategis Sustainability and Environment'],
            8 => ['code' => 'SIJAK', 'name' => 'Cabang Jakarta'],
            9 => ['code' => 'SISUB', 'name' => 'Cabang Surabaya'],
            10 => ['code' => 'SIMAK', 'name' => 'Cabang Makassar'],
            11 => ['code' => 'SIBAT', 'name' => 'Cabang Batam'],
            12 => ['code' => 'SIBPP', 'name' => 'Cabang Balikpapan'],
            13 => ['code' => 'SIMED', 'name' => 'Cabang Medan'],
            14 => ['code' => 'SIPAL', 'name' => 'Cabang Palembang'],
            15 => ['code' => 'SIPKU', 'name' => 'Cabang Pekanbaru'],
            16 => ['code' => 'SISMA', 'name' => 'Cabang Semarang'],
            17 => ['code' => 'SISG', 'name' => 'Cabang Singapura'],
            18 => ['code' => 'SIBJB', 'name' => 'Cabang Banjarbaru'],
            19 => ['code' => 'SISMD', 'name' => 'Cabang Samarinda'],
            20 => ['code' => 'SITJR', 'name' => 'Cabang Tanjung Redeb'],
            21 => ['code' => 'SIBER', 'name' => 'Cabang Berau'],
            22 => ['code' => 'SIGRS', 'name' => 'Cabang Gresik'],
            23 => ['code' => 'SISGT', 'name' => 'Cabang Sangatta'],
            24 => ['code' => 'SIBJM', 'name' => 'Cabang Banjarmasin'],
            25 => ['code' => 'SITJG', 'name' => 'Cabang Tanjung'],
            26 => ['code' => 'SP', 'name' => 'Sekretariat Perusahaan'],
            27 => ['code' => 'SPI', 'name' => 'Satuan Pengawasan Intern'],
            28 => ['code' => 'DRP2B', 'name' => 'Divisi Riset, Pemasaran dan Pengembangan Bisnis'],
            29 => ['code' => 'DOP', 'name' => 'Divisi Operasi'],
            30 => ['code' => 'DKA', 'name' => 'Divisi Keuangan dan Akuntansi'],
            31 => ['code' => 'DPKMR', 'name' => 'Divisi Perencanaan Korporat dan Manajemen Risiko'],
            32 => ['code' => 'DMA', 'name' => 'Divisi Manajemen Aset'],
            33 => ['code' => 'DHC', 'name' => 'Divisi Human Capital'],
            34 => ['code' => 'DTI', 'name' => 'Divisi Teknologi Informasi'],
            35 => ['code' => 'STO', 'name' => 'Strategic Transformation Office'],
            36 => ['code' => 'UTJSL', 'name' => 'Unit Tanggung Jawab Sosial dan Lingkungan'],
            37 => ['code' => 'PENUGASKAR', 'name' => 'PT BKI (Persero) - Penugasan'],
            38 => ['code' => 'PTINPSEKSI', 'name' => 'PT Surveyor Indonesia Sertifikasi'],
            39 => ['code' => 'KSOSCSI', 'name' => 'KSO SCI-SI'],
            40 => ['code' => 'SCCI', 'name' => 'SCCI'],
        ];

        $now = now();
        foreach ($data as $id => $val) {
            DB::table('units')->insert([
                'id' => $id,
                'code' => $val['code'],
                'name' => $val['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}