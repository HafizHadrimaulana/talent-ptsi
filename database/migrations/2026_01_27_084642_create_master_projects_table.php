<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('project_code'); 
        Schema::create('master_projects', function (Blueprint $table) {
            $table->id();
            $table->string('nama_unit')->nullable();
            $table->string('kode_project')->index(); 
            $table->text('nama_project')->nullable(); 
            $table->decimal('nilai_kontrak', 20, 2)->nullable(); 
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_akhir')->nullable();
            $table->string('portofolio_code')->nullable();
            $table->string('portofolio_name')->nullable();
            $table->string('nama_klien')->nullable();
            $table->integer('sync_year')->nullable()->index(); 
            $table->timestamps();
            $table->unique(['kode_project', 'sync_year'], 'unique_project_year'); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('master_projects');
    }
};