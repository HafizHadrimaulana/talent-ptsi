<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','employee_id')) {
                $table->string('employee_id')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users','unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('employee_id')->index();
            }
            if (!Schema::hasColumn('users','job_title')) {
                $table->string('job_title')->nullable()->after('unit_id');
            }
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users','job_title')) $table->dropColumn('job_title');
            if (Schema::hasColumn('users','unit_id')) $table->dropColumn('unit_id');
            if (Schema::hasColumn('users','employee_id')) $table->dropColumn('employee_id');
        });
    }
};
