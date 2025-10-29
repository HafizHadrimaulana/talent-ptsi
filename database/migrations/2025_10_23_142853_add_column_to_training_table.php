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
        Schema::table('training', function (Blueprint $table) {
            $table->unsignedBigInteger('status_approval_training_id')->default(1);

            $table->foreign('status_approval_training_id')
                ->references('id')
                ->on('status_approval_training');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training', function (Blueprint $table) {
            $table->dropForeign(['status_approval_training_id']);
            $table->dropColumn('status_approval_training_id');
        });
    }
};
