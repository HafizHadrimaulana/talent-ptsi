<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('recruitment_requests')) {
            Schema::table('recruitment_requests', function (Blueprint $table) {
                // Tambah kolom budget_source_type jika belum ada
                if (!Schema::hasColumn('recruitment_requests', 'budget_source_type')) {
                    $table->string('budget_source_type', 100)->nullable()->after('target_start_date');
                }
                
                // Tambah kolom budget_ref jika belum ada
                if (!Schema::hasColumn('recruitment_requests', 'budget_ref')) {
                    $table->string('budget_ref', 255)->nullable()->after('budget_source_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recruitment_requests')) {
            Schema::table('recruitment_requests', function (Blueprint $table) {
                if (Schema::hasColumn('recruitment_requests', 'budget_ref')) {
                    $table->dropColumn('budget_ref');
                }
                if (Schema::hasColumn('recruitment_requests', 'budget_source_type')) {
                    $table->dropColumn('budget_source_type');
                }
            });
        }
    }
};
