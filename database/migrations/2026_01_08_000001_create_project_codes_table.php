<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_code', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->nullable();
            $table->text('nama_klien')->nullable();
            $table->string('unit_kerja_id')->nullable();
            $table->string('unit_kerja_nama')->nullable();
            $table->string('unit_pengelola_id')->nullable();
            $table->string('unit_pengelola_nama')->nullable();
            $table->text('nama_potensial')->nullable();
            $table->string('jenis_kontrak')->nullable();
            $table->text('nama_proyek')->nullable();
            $table->string('project_status')->nullable();
            $table->timestamps();

            // $table->unique(['client_id','nama_proyek'], 'project_code_client_proyek_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_code');
    }
}
