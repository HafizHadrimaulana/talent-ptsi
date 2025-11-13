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
        Schema::create('training', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('status_approval_training_id');
            $table->unsignedBigInteger('training_temp_id');

            $table->string('nama_pelatihan');
            $table->string('nama_peserta');

            $table->date('realisasi_date')->nullable();

            $table->string('certificate_document')->nullable();
            $table->string('evaluation')->nullable();

            $table->foreign('status_approval_training_id')
                ->references('id')
                ->on('status_approval_training')
                ->onDelete('cascade');
            $table->foreign('training_temp_id')
                ->references('id')
                ->on('training_temp')
                ->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training');
    }
};