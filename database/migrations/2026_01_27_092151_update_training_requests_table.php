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
        Schema::table('training_request', function (Blueprint $table) {
            $table->boolean('is_ikatan_dinas_filled')->default(false)->after('is_evaluated');
            $table->string('signed_document_path')->nullable()->after('is_ikatan_dinas_filled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_request', function (Blueprint $table) {
            $table->dropColumn('is_ikatan_dinas_filled');
            $table->dropColumn('signed_document_path');
        });
    }
};
