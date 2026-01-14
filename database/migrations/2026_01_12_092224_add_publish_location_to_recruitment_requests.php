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
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->string('publish_location')->nullable()->after('publish_end_date');
        });
    }

    public function down()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->dropColumn('publish_location');
        });
    }
};
