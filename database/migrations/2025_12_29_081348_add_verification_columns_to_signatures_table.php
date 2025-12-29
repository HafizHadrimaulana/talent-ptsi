<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            // Cek apakah kolom belum ada sebelum menambahkannya agar tidak error
            if (!Schema::hasColumn('signatures', 'geo_lat')) {
                $table->string('geo_lat', 50)->nullable()->after('signature_draw_data');
            }
            if (!Schema::hasColumn('signatures', 'geo_lng')) {
                $table->string('geo_lng', 50)->nullable()->after('geo_lat');
            }
            if (!Schema::hasColumn('signatures', 'snapshot_data')) {
                $table->longText('snapshot_data')->nullable()->after('geo_lng'); // Foto base64 bisa panjang
            }
            if (!Schema::hasColumn('signatures', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('snapshot_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn(['geo_lat', 'geo_lng', 'snapshot_data', 'ip_address']);
        });
    }
};