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
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            
            // 1. RENAME KOLOM (Jika masih pakai nama lama)
            // Ubah 'full_name' jadi 'name'
            if (Schema::hasColumn('recruitment_applicants', 'full_name') && !Schema::hasColumn('recruitment_applicants', 'name')) {
                $table->renameColumn('full_name', 'name');
            }
            
            // Ubah 'notes' jadi 'hr_notes'
            if (Schema::hasColumn('recruitment_applicants', 'notes') && !Schema::hasColumn('recruitment_applicants', 'hr_notes')) {
                $table->renameColumn('notes', 'hr_notes');
            }

            // 2. HAPUS KOLOM LAMA (Yang ada di teman, tapi tidak ada di Anda)
            if (Schema::hasColumn('recruitment_applicants', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
            if (Schema::hasColumn('recruitment_applicants', 'nik_number')) {
                $table->dropColumn('nik_number');
            }
            if (Schema::hasColumn('recruitment_applicants', 'attachments')) {
                $table->dropColumn('attachments');
            }

            // 3. TAMBAH KOLOM BARU (Yang ada di Anda, tapi hilang di teman)
            
            // Caga-jaga jika rename gagal atau kolom belum ada sama sekali
            if (!Schema::hasColumn('recruitment_applicants', 'name')) {
                $table->string('name')->after('user_id');
            }

            if (!Schema::hasColumn('recruitment_applicants', 'university')) {
                $table->string('university')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('recruitment_applicants', 'major')) {
                $table->string('major')->nullable()->after('university');
            }

            if (!Schema::hasColumn('recruitment_applicants', 'cv_path')) {
                $table->string('cv_path')->nullable()->after('major');
            }

            // Pastikan interview_schedule ada
            if (!Schema::hasColumn('recruitment_applicants', 'interview_schedule')) {
                $table->dateTime('interview_schedule')->nullable()->after('status');
            }

            // Pastikan hr_notes ada (jika proses rename di atas tidak tereksekusi karena 'notes' tidak ada)
            if (!Schema::hasColumn('recruitment_applicants', 'hr_notes')) {
                $table->text('hr_notes')->nullable()->after('interview_schedule');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Logika rollback (mengembalikan ke kondisi "berantakan" teman Anda - opsional)
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_applicants', 'name')) {
                $table->renameColumn('name', 'full_name');
            }
            if (Schema::hasColumn('recruitment_applicants', 'hr_notes')) {
                $table->renameColumn('hr_notes', 'notes');
            }
            // Kembalikan kolom yang dihapus
            if (!Schema::hasColumn('recruitment_applicants', 'unit_id')) $table->bigInteger('unit_id')->nullable();
            if (!Schema::hasColumn('recruitment_applicants', 'nik_number')) $table->string('nik_number')->nullable();
            if (!Schema::hasColumn('recruitment_applicants', 'attachments')) $table->json('attachments')->nullable();
            
            // Hapus kolom baru
            $table->dropColumn(['university', 'major', 'cv_path', 'interview_schedule']);
        });
    }
};