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
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            // Menambahkan kolom position_applied setelah recruitment_request_id
            $table->string('position_applied')->nullable()->after('recruitment_request_id');
        });
    }

    public function down()
    {
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            $table->dropColumn('position_applied');
        });
    }
};
