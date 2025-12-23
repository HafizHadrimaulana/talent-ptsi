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
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_requests', 'meta')) {
                $table->json('meta')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_requests', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
