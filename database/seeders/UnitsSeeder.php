<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // peta NAME → CODE (tanpa spasi)
            $map = [
                // Head Office
                'SI Head Office' => 'SIHO',

                // DBS
                'Divisi Bisnis Strategis Oil, Gas and Renewable Energy' => 'DBSOGRE',
                'Divisi Bisnis Strategis Coal and Mineral'              => 'DBSCNM',
                'Divisi Bisnis Strategis Government and Institution'    => 'DBSGNI',
                'Divisi Bisnis Strategis Industrial Services'           => 'DBSINS',
                'Divisi Bisnis Strategis Infrastructure and Transportation' => 'DBSINT',
                'Divisi Bisnis Strategis Sustainability and Environment'    => 'DBSSNE',

                // Cabang
                'Cabang Jakarta'     => 'SIJAK',
                'Cabang Surabaya'    => 'SISUB',
                'Cabang Makassar'    => 'SIMAK',
                'Cabang Batam'       => 'SIBAT',
                'Cabang Balikpapan'  => 'SIBPP',
                'Cabang Medan'       => 'SIMED',
                'Cabang Palembang'   => 'SIPAL',
                'Cabang Pekanbaru'   => 'SIPKU',
                'Cabang Semarang'    => 'SISMA',
                'Cabang Singapura'   => 'SISG',
                'Cabang Banjarbaru'  => 'SIBJB',
                'Cabang Samarinda'   => 'SISMD',
                'Cabang Tanjung Redeb' => 'SITJR',
                'Cabang Berau'       => 'SIBER',
                'Cabang Gresik'      => 'SIGRS',
                'Cabang Sangatta'    => 'SISGT',
                'Cabang Banjarmasin' => 'SIBJM',
                'Cabang Tanjung'     => 'SITJG',

                // Enabler / Pusat
                'Sekretariat Perusahaan'                           => 'SP',
                'Satuan Pengawasan Intern'                         => 'SPI',
                'Divisi Riset, Pemasaran dan Pengembangan Bisnis'  => 'DRP2B',
                'Divisi Operasi'                                   => 'DOP',
                'Divisi Keuangan dan Akuntansi'                    => 'DKA',
                'Divisi Perencanaan Korporat dan Manajemen Risiko' => 'DPKMR',
                'Divisi Manajemen Aset'                            => 'DMA',
                'Divisi Human Capital'                             => 'DHC',
                'Divisi Teknologi Informasi'                       => 'DTI',
                'Strategic Transformation Office'                  => 'STO',
                'Unit Tanggung Jawab Sosial dan Lingkungan'        => 'UTJSL',

                // Entitas terkait (jika ada pada units)
                'PT BKI (Persero) - Penugasan'                     => 'PENUGASKAR',
                'PT Surveyor Indonesia Sertifikasi'                => 'PTINPSEKSI',
                'KSO SCI-SI'                                       => 'KSOSCSI',
                'SCCI'                                             => 'SCCI',
            ];

            foreach ($map as $name => $code) {
                // Semua baris yang punya NAME ini (mungkin lebih dari 1)
                $rows = DB::table('units')->where('name', $name)->orderBy('id', 'asc')->get();
                if ($rows->isEmpty()) {
                    // name tidak ada di DB → skip (idempotent)
                    continue;
                }

                // Tetapkan satu "keeper" (id terkecil)
                $keeper = $rows->first();

                // 1) Jika ada baris lain (name beda) yang sudah pakai CODE target, bebaskan dulu
                $conflicts = DB::table('units')
                    ->where('code', $code)
                    ->where('id', '<>', $keeper->id)
                    ->get();

                foreach ($conflicts as $conf) {
                    DB::table('units')
                        ->where('id', $conf->id)
                        ->update(['code' => '__TMP__' . $code . '_' . $conf->id]);
                }

                // 2) Set CODE final di keeper (idempotent)
                DB::table('units')->where('id', $keeper->id)->update(['code' => $code]);

                // 3) Untuk baris lain dengan NAME sama (duplikat), beri code unik agar tidak nabrak unique
                foreach ($rows->slice(1) as $dup) {
                    // kalau dia sudah punya code sama dengan $code, ubah jadi __DUP__...
                    DB::table('units')
                        ->where('id', $dup->id)
                        ->update(['code' => '__DUP__' . $code . '_' . $dup->id]);
                }
            }
        });
    }
}
