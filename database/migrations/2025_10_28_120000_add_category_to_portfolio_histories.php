<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('portfolio_histories')) {
            Schema::table('portfolio_histories', function (Blueprint $t) {
                if (!Schema::hasColumn('portfolio_histories', 'category')) {
                    $t->string('category', 32)->nullable()->after('employee_id');
                }
                if (!Schema::hasColumn('portfolio_histories', 'organization') && Schema::hasColumn('portfolio_histories', 'unit_name') === false) {
                    // aman-aman aja kalau gak ada; skip buat jaga-jaga
                }
                $t->index(['person_id', 'category', 'start_date'], 'ph_person_cat_start_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('portfolio_histories')) {
            Schema::table('portfolio_histories', function (Blueprint $t) {
                if (Schema::hasColumn('portfolio_histories', 'category')) {
                    $t->dropIndex('ph_person_cat_start_idx');
                    $t->dropColumn('category');
                }
            });
        }
    }
};
