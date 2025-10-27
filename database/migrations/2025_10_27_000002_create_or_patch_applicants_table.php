<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('applicants')) {
            Schema::create('applicants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('unit_id')->nullable()->index();
                $table->string('full_name');
                $table->string('position_applied')->nullable();
                $table->string('status')->default('applied')->index(); // e.g. applied|shortlisted|selected|rejected
                $table->timestamps();

                // Jika nanti mau FK ke units, bisa ditambah sesuai kebutuhan:
                // $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
            });
        } else {
            // Patch kolom wajib jika tabel sudah ada tapi kolom belum lengkap
            Schema::table('applicants', function (Blueprint $table) {
                if (!Schema::hasColumn('applicants', 'unit_id')) {
                    $table->unsignedBigInteger('unit_id')->nullable()->index()->after('id');
                }
                if (!Schema::hasColumn('applicants', 'full_name')) {
                    $table->string('full_name')->after('unit_id');
                }
                if (!Schema::hasColumn('applicants', 'position_applied')) {
                    $table->string('position_applied')->nullable()->after('full_name');
                }
                if (!Schema::hasColumn('applicants', 'status')) {
                    $table->string('status')->default('applied')->index()->after('position_applied');
                }
                // created_at/updated_at
                if (!Schema::hasColumn('applicants', 'created_at') && !Schema::hasColumn('applicants', 'updated_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    public function down(): void
    {
        // Hapus tabel (aman kalau memang baru dibuat untuk fitur ini)
        if (Schema::hasTable('applicants')) {
            Schema::drop('applicants');
        }
    }
};
