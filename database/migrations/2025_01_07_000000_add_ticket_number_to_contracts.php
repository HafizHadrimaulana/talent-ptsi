<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'ticket_number')) {
                $table->string('ticket_number')->nullable()->after('contract_no')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'ticket_number')) {
                $table->dropColumn('ticket_number');
            }
        });
    }
};