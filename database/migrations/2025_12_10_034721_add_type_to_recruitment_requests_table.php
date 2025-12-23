<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            // 1. Tambahkan kolom 'type' setelah kolom 'title' (agar rapi)
            if (!Schema::hasColumn('recruitment_requests', 'type')) {
                $table->string('type')->nullable()->after('title');
            }
        });

        // 2. Isi data lama (Seeding Data)
        // Karena kita tidak tahu mana yang 'Perpanjang Kontrak' secara pasti, 
        // kita set default semua data lama menjadi 'Rekrutmen' agar saat export tidak kosong.
        // Nanti Anda bisa mengubah manual data yang seharusnya 'Perpanjang Kontrak' via database/aplikasi.
        DB::table('recruitment_requests')->whereNull('type')->update(['type' => 'Rekrutmen']);
    }

    public function down()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_requests', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};