<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('recruitment_requests')) {
            Schema::table('recruitment_requests', function (Blueprint $table) {
                // Tambah kolom yang dipakai di query kalau belum ada
                if (!Schema::hasColumn('recruitment_requests', 'title')) {
                    $table->string('title')->nullable()->after('id');
                }
                if (!Schema::hasColumn('recruitment_requests', 'position')) {
                    $table->string('position')->nullable()->after('title');
                }
                if (!Schema::hasColumn('recruitment_requests', 'headcount')) {
                    $table->unsignedInteger('headcount')->default(1)->after('position');
                }
                if (!Schema::hasColumn('recruitment_requests', 'status')) {
                    $table->string('status')->default('draft')->after('headcount');
                }
                // Kolom created_at/updated_at biasanya sudah ada.
                // Jangan tambahkan timestamps lagi biar aman.
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recruitment_requests')) {
            Schema::table('recruitment_requests', function (Blueprint $table) {
                // Rollback hanya kolom yang kita tambahkan
                if (Schema::hasColumn('recruitment_requests', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('recruitment_requests', 'headcount')) {
                    $table->dropColumn('headcount');
                }
                if (Schema::hasColumn('recruitment_requests', 'position')) {
                    $table->dropColumn('position');
                }
                if (Schema::hasColumn('recruitment_requests', 'title')) {
                    $table->dropColumn('title');
                }
            });
        }
    }
};
