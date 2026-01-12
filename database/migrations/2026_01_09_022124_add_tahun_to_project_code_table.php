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
        Schema::table('project_code', function (Blueprint $table) {
            // Tambah kolom tahun untuk membedakan scope sync
            $table->integer('tahun')->nullable()->index()->after('project_status');
        });
    }

    public function down()
    {
        Schema::table('project_code', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });
    }
};
