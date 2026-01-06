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
            $table->text('description')->nullable()->after('is_published');
        });
    }

    public function down()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
