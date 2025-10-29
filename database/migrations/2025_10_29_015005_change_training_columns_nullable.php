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
            $table->integer('no')->nullable()->change();
            $table->string('nik')->nullable()->change();
            $table->string('nama_peserta')->nullable()->change();
            $table->string('status_pegawai')->nullable()->change();
            $table->string('jabatan_saat_ini')->nullable()->change();
            $table->string('unit_kerja')->nullable()->change();

            $table->string('judul_sertifikasi')->nullable()->change();
            $table->string('penyelenggara')->nullable()->change();
            $table->string('jumlah_jam')->nullable()->change();

            $table->string('waktu_pelaksanaan')->nullable()->change();
            $table->string('nama_proyek')->nullable()->change();

            $table->decimal('biaya_pelatihan', 15, 2)->nullable()->change();
            $table->decimal('uhpd', 15, 2)->nullable()->change();
            $table->decimal('biaya_akomodasi', 15, 2)->nullable()->change();
            $table->decimal('estimasi_total_biaya', 15, 2)->nullable()->change();

            $table->string('alasan')->nullable();

            $table->string('jenis_portofolio')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training', function (Blueprint $table) {
            $table->integer('no')->nullable(false)->change();
            $table->string('nik')->nullable(false)->change();
            $table->string('nama_peserta')->nullable(false)->change();
            $table->string('status_pegawai')->nullable(false)->change();
            $table->string('jabatan_saat_ini')->nullable(false)->change();
            $table->string('unit_kerja')->nullable(false)->change();

            $table->string('judul_sertifikasi')->nullable(false)->change();
            $table->string('penyelenggara')->nullable(false)->change();
            $table->string('jumlah_jam')->nullable(false)->change();

            $table->string('waktu_pelaksanaan')->nullable(false)->change();
            $table->string('nama_proyek')->nullable(false)->change();
            
            $table->decimal('biaya_pelatihan', 15, 2)->nullable(false)->change();
            $table->decimal('uhpd', 15, 2)->nullable(false)->change();
            $table->decimal('biaya_akomodasi', 15, 2)->nullable(false)->change();
            $table->decimal('estimasi_total_biaya', 15, 2)->nullable(false)->change();
            
            $table->string('alasan')->nullable(false);

            $table->string('jenis_portofolio')->nullable(false)->change();
        });
    }
};
