<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom applicant_id ke tabel contracts.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'applicant_id')) {
                // Asumsi ID pelamar pakai ULID/CHAR(26)
                $table->char('applicant_id', 26)
                    ->nullable()
                    ->after('employee_id');

                $table->index('applicant_id', 'contracts_applicant_id_index');
            }
        });
    }

    /**
     * Rollback perubahan.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'applicant_id')) {
                $table->dropIndex('contracts_applicant_id_index');
                $table->dropColumn('applicant_id');
            }
        });
    }
};
