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
        Schema::create('training_anggaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->index()->nullable();
            
            $table->string('limit_anggaran')->nullable();

            $table->foreign('unit_id')
                ->references('id')->on('units')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_anggaran');
    }
};
