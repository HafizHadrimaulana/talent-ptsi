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

            $table->unsignedBigInteger('file_training_id');

            $table->integer('no');
            $table->string('nik');
            $table->string('nama_peserta');
            $table->string('status_pegawai');
            $table->string('jabatan_saat_ini');
            $table->string('unit_kerja');

            $table->string('judul_sertifikasi');
            $table->string('penyelenggara');
            $table->integer('jumlah_jam');

            $table->string('waktu_pelaksanaan');
            $table->string('nama_proyek');

            $table->decimal('biaya_pelatihan', 15, 2)->nullable();
            $table->decimal('uhpd', 15, 2)->nullable();
            $table->decimal('biaya_akomodasi', 15, 2)->nullable();
            $table->decimal('estimasi_total_biaya', 15, 2)->nullable();

            $table->string('jenis_portofolio');

            $table->timestamps();

            $table->foreign('file_training_id')
                ->references('id')
                ->on('file_training')
                ->onDelete('cascade');
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
