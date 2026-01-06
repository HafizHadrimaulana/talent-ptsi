<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('applicants') && !Schema::hasTable('recruitment_applicants')) {
            Schema::rename('applicants', 'recruitment_applicants');
        }

        Schema::table('recruitment_applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('recruitment_applicants', 'position_applied')) {
                $table->string('position_applied')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_applicants', 'position_applied')) {
                $table->dropColumn('position_applied');
            }
        });
        
        if (Schema::hasTable('recruitment_applicants') && !Schema::hasTable('applicants')) {
            Schema::rename('recruitment_applicants', 'applicants');
        }
    }
};