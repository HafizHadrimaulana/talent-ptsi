<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Jangan lupa import ini

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Masukkan data Unit ID 100
        DB::table('units')->insertOrIgnore([
            'id'         => 100, // set ID 100
            'name'       => 'Pelamar External',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus data jika rollback
        DB::table('units')->where('id', 100)->delete();
    }
};