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

            $table->unsignedBigInteger('status_training_id');

            $table->string('nama_pelatihan');
            $table->string('nama_peserta');

            $table->date('start_date');
            $table->date('realisasi_date');

            $table->string('dokumen_sertifikasi');

            $table->foreign('status_training_id')
                ->references('id')
                ->on('status_approval_training')
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