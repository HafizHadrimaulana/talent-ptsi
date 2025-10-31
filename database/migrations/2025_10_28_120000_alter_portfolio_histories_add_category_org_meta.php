<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('portfolio_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('portfolio_histories', 'category')) {
                $table->string('category', 30)->nullable()->after('employee_id')->index();
            }
            if (!Schema::hasColumn('portfolio_histories', 'organization')) {
                $table->string('organization', 150)->nullable()->after('title');
            }
            if (!Schema::hasColumn('portfolio_histories', 'description')) {
                $table->text('description')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('portfolio_histories', 'meta')) {
                $table->json('meta')->nullable()->after('description');
            }
            // indeks bantu
            if (!Schema::hasColumn('portfolio_histories', 'person_id')) {
                // aman-aman aja kalau sudah ada
            } else {
                $table->index(['person_id', 'category']);
                $table->index(['person_id', 'start_date']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_histories', function (Blueprint $table) {
            if (Schema::hasColumn('portfolio_histories', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('portfolio_histories', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('portfolio_histories', 'organization')) {
                $table->dropColumn('organization');
            }
            if (Schema::hasColumn('portfolio_histories', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
