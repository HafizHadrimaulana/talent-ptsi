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
        Schema::create('training_references', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->index()->nullable();

            $table->string('judul_sertifikasi')->nullable();
            $table->string('penyelenggara')->nullable();

            $table->string('jumlah_jam')->nullable();
            $table->string('waktu_pelaksanaan')->nullable();
            
            $table->decimal('biaya_pelatihan', 15, 2)->nullable();

            $table->string('nama_proyek')->nullable();
            $table->string('jenis_portofolio')->nullable();
            $table->string('fungsi')->nullable();

            $table->string('status_training_reference')->default('active');

            $table->timestamps();
            
            $table->foreign('unit_id')
                ->references('id')->on('units')
                ->onDelete('cascade');

            $table->unique(['judul_sertifikasi', 'penyelenggara', 'unit_id'], 'training_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_references');
    }
};
