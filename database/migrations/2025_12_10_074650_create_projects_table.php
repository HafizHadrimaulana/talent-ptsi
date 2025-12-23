<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Kita gunakan Schema::hasTable untuk handling jika tabel sudah ada tapi strukturnya salah
        if (Schema::hasTable('projects')) {
            // Jika tabel sudah ada, kita drop dulu agar bersih (hati-hati data hilang)
            // Atau jika project ini masih dev, drop aman.
            Schema::drop('projects');
        }

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique(); // Kode Project (PRJ-XXX)
            $table->string('project_name');           // Nama Project
            
            // Relasi ke tabel locations
            // Pastikan tipe data sama dengan id di tabel locations (bigint unsigned)
            $table->unsignedBigInteger('location_id')->nullable(); 
            
            // Foreign key (Opsional, menjaga integritas data)
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');

            $table->string('document_path')->nullable(); // Untuk menyimpan path file upload
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
    }
};