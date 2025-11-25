<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // tambah employment_type setelah unit_id
            if (! Schema::hasColumn('contracts', 'employment_type')) {
                $table->string('employment_type', 40)
                    ->nullable()
                    ->after('unit_id');
            }

            // tambah budget_source_type setelah employment_type
            if (! Schema::hasColumn('contracts', 'budget_source_type')) {
                $table->string('budget_source_type', 40)
                    ->nullable()
                    ->after('employment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'employment_type')) {
                $table->dropColumn('employment_type');
            }

            if (Schema::hasColumn('contracts', 'budget_source_type')) {
                $table->dropColumn('budget_source_type');
            }
        });
    }
};
