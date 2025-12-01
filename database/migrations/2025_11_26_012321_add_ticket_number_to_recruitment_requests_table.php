<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->unique()->after('id');
            $table->index('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->dropIndex(['ticket_number']);
            $table->dropColumn('ticket_number');
        });
    }
};
