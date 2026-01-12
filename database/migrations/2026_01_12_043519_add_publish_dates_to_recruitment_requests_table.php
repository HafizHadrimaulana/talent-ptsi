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
            // Menambahkan kolom tanggal publish setelah kolom description
            $table->date('publish_start_date')->nullable()->after('description');
            $table->date('publish_end_date')->nullable()->after('publish_start_date');
        });
    }

    public function down()
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->dropColumn(['publish_start_date', 'publish_end_date']);
        });
    }
};
