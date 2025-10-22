<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ===== 1) Directorates =====
        $directorates = [
            ['code' => 'DBS',  'name' => 'Divisi Bisnis Strategis'],
            ['code' => 'CAB',  'name' => 'Cabang'],
            ['code' => 'ENAB', 'name' => 'Enabler'],
        ];

        foreach ($directorates as $d) {
            DB::table('directorates')->updateOrInsert(
                ['code' => $d['code']],
                [
                    'name'       => $d['name'],
                    'updated_at' => now(),
                    // preserve created_at if exists
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }

        // helper ambil id directorate via code
        $dirId = fn(string $code) =>
            DB::table('directorates')->where('code', $code)->value('id');

        $DBS  = $dirId('DBS');
        $CAB  = $dirId('CAB');
        $ENAB = $dirId('ENAB');

        // ===== 2) Units (27 unit total) =====
        // Nama sementara = sama dengan code (aman). Mau ganti ke nama panjang? tinggal edit 'name'.
        $units = [
            // === DBS (6)
            ['code' => 'OGRE',  'name' => 'OGRE',  'directorate_id' => $DBS],
            ['code' => 'CNM',   'name' => 'CNM',   'directorate_id' => $DBS],
            ['code' => 'GNI',   'name' => 'GNI',   'directorate_id' => $DBS],
            ['code' => 'INS',   'name' => 'INS',   'directorate_id' => $DBS],
            ['code' => 'INT',   'name' => 'INT',   'directorate_id' => $DBS],
            ['code' => 'SNE',   'name' => 'SNE',   'directorate_id' => $DBS],

            // === Cabang (10)
            ['code' => 'SIBAT', 'name' => 'SIBAT', 'directorate_id' => $CAB],
            ['code' => 'SIBPP', 'name' => 'SIBPP', 'directorate_id' => $CAB],
            ['code' => 'SIMED', 'name' => 'SIMED', 'directorate_id' => $CAB],
            ['code' => 'SIPAL', 'name' => 'SIPAL', 'directorate_id' => $CAB],
            ['code' => 'SIPKU', 'name' => 'SIPKU', 'directorate_id' => $CAB],
            ['code' => 'SISMA', 'name' => 'SISMA', 'directorate_id' => $CAB],
            ['code' => 'SISG',  'name' => 'SISG',  'directorate_id' => $CAB],
            ['code' => 'SIJAK', 'name' => 'SIJAK', 'directorate_id' => $CAB],
            ['code' => 'SISUB', 'name' => 'SISUB', 'directorate_id' => $CAB],
            ['code' => 'SIMAK', 'name' => 'SIMAK', 'directorate_id' => $CAB],

            // === Enabler (11)
            ['code' => 'SP',    'name' => 'SP',    'directorate_id' => $ENAB],
            ['code' => 'SPI',   'name' => 'SPI',   'directorate_id' => $ENAB],
            ['code' => 'DRP2B', 'name' => 'DRP2B', 'directorate_id' => $ENAB],
            ['code' => 'DKA',   'name' => 'DKA',   'directorate_id' => $ENAB],
            ['code' => 'DPKMR', 'name' => 'DPKMR', 'directorate_id' => $ENAB],
            ['code' => 'DMA',   'name' => 'DMA',   'directorate_id' => $ENAB],
            ['code' => 'DHC',   'name' => 'DHC',   'directorate_id' => $ENAB],
            ['code' => 'DTI',   'name' => 'DTI',   'directorate_id' => $ENAB],
            ['code' => 'STO',   'name' => 'STO',   'directorate_id' => $ENAB],
            ['code' => 'UTJSL', 'name' => 'UTJSL', 'directorate_id' => $ENAB],
            ['code' => 'DOP',   'name' => 'DOP',   'directorate_id' => $ENAB],
        ];

        foreach ($units as $u) {
            DB::table('units')->updateOrInsert(
                ['code' => $u['code']],
                [
                    'name'            => $u['name'],
                    'directorate_id'  => $u['directorate_id'],
                    'updated_at'      => now(),
                    'created_at'      => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }

    public function down(): void
    {
        // rollback—hapus units & directorates yang kita tanam (berdasarkan code agar aman)
        $unitCodes = [
            // DBS
            'OGRE','CNM','GNI','INS','INT','SNE',
            // CAB
            'SIBAT','SIBPP','SIMED','SIPAL','SIPKU','SISMA','SISG','SIJAK','SISUB','SIMAK',
            // ENAB
            'SP','SPI','DRP2B','DKA','DPKMR','DMA','DHC','DTI','STO','UTJSL','DOP',
        ];
        DB::table('units')->whereIn('code', $unitCodes)->delete();

        $dirCodes = ['DBS','CAB','ENAB'];
        DB::table('directorates')->whereIn('code', $dirCodes)->delete();
    }
};
