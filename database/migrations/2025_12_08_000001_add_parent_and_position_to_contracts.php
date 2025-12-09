<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'parent_contract_id')) {
                $table->unsignedBigInteger('parent_contract_id')->nullable()->after('position_level_id');
                $table->foreign('parent_contract_id')->references('id')->on('contracts')->nullOnDelete();
            }
            if (!Schema::hasColumn('contracts', 'position_name')) {
                $table->string('position_name', 191)->nullable()->after('position_level_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'parent_contract_id')) {
                $table->dropForeign(['parent_contract_id']);
                $table->dropColumn('parent_contract_id');
            }
            if (Schema::hasColumn('contracts', 'position_name')) {
                $table->dropColumn('position_name');
            }
        });
    }
};
