<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'person_id')) {
                $table->ulid('person_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id', 64)->nullable()->after('person_id')->index();
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'employee_id')) {
                $table->dropColumn('employee_id');
            }
            if (Schema::hasColumn('users', 'person_id')) {
                $table->dropColumn('person_id');
            }
        });
    }
};
