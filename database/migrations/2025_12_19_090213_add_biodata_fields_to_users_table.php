<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kita tambahkan kolom biodata ke tabel USERS
            // agar menjadi Data Induk Pelamar
            
            if (!Schema::hasColumn('users', 'nik')) {
                $table->string('nik', 20)->nullable()->after('email'); // Sesuai form NIK
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('users', 'education_level')) {
                $table->string('education_level', 50)->nullable()->after('phone'); // Untuk dropdown Jenjang
            }
            if (!Schema::hasColumn('users', 'education')) {
                $table->string('education')->nullable()->after('education_level'); // Untuk Jurusan/Institusi
            }
            if (!Schema::hasColumn('users', 'experience')) {
                $table->text('experience')->nullable()->after('education');
            }
            if (!Schema::hasColumn('users', 'cv_path')) {
                $table->string('cv_path')->nullable()->after('experience');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'phone', 'education_level', 'education', 'experience', 'cv_path']);
        });
    }
};