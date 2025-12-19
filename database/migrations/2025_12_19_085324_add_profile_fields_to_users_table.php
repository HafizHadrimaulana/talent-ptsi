<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan kolom yang kurang agar tidak error saat save
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'nik')) {
                $table->string('nik')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'education_level')) {
                $table->string('education_level')->nullable()->after('nik');
            }
            if (!Schema::hasColumn('users', 'education')) {
                $table->string('education')->nullable()->after('education_level');
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
            $table->dropColumn(['phone', 'nik', 'education_level', 'education', 'experience', 'cv_path']);
        });
    }
};