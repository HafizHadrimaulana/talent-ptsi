<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('recruitment_request_id')->nullable()->after('contract_type')->index();
            
            $table->foreign('recruitment_request_id')
                  ->references('id')
                  ->on('recruitment_requests')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['recruitment_request_id']);
            $table->dropColumn('recruitment_request_id');
        });
    }
};
