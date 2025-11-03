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
        Schema::create('training_temp', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('file_training_id');
            $table->unsignedBigInteger('status_approval_training_id');

            $table->string('jenis_pelatihan')->nullable();
            $table->string('nik')->nullable();
            $table->string('nama_peserta')->nullable();
            $table->string('status_pegawai')->nullable();
            $table->string('jabatan_saat_ini')->nullable();
            $table->string('unit_kerja')->nullable();

            $table->string('judul_sertifikasi')->nullable();
            $table->string('penyelenggara')->nullable();

            $table->string('jumlah_jam')->nullable();
            $table->string('waktu_pelaksanaan')->nullable();
            
            $table->decimal('biaya_pelatihan', 15, 2)->nullable();
            $table->decimal('uhpd', 15, 2)->nullable();
            $table->decimal('biaya_akomodasi', 15, 2)->nullable();
            $table->decimal('estimasi_total_biaya', 15, 2)->nullable();

            $table->string('nama_proyek')->nullable();
            $table->string('jenis_portofolio')->nullable();
            $table->string('fungsi')->nullable();

            $table->string('alasan')->nullable();
            
            $table->foreign('file_training_id')
                ->references('id')
                ->on('file_training')
                ->onDelete('cascade');

            $table->foreign('status_approval_training_id')
                ->references('id')
                ->on('status_approval_training')
                ->onDelete('cascade');
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_temp');
    }
};
