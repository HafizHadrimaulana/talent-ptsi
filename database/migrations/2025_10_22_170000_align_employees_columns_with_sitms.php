<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'employee_id')) {
                $table->string('employee_id', 64)->nullable()->after('person_id')->index();
            }
            if (!Schema::hasColumn('employees', 'id_sitms')) {
                $table->string('id_sitms', 64)->nullable()->after('employee_id')->index();
            }
        });

        // Backfill dari kolom lama jika ada
        if (Schema::hasColumn('employees', 'sitms_employee_id')) {
            DB::statement("UPDATE employees SET employee_id = COALESCE(employee_id, sitms_employee_id)");
        }
        if (Schema::hasColumn('employees', 'sitms_id')) {
            DB::statement("UPDATE employees SET id_sitms = COALESCE(id_sitms, sitms_id)");
        }

        Schema::table('employees', function (Blueprint $table) {
            // Hapus kolom lama jika ada
            if (Schema::hasColumn('employees', 'sitms_employee_id')) {
                $table->dropColumn('sitms_employee_id');
            }
            if (Schema::hasColumn('employees', 'sitms_id')) {
                $table->dropColumn('sitms_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'sitms_employee_id')) {
                $table->string('sitms_employee_id', 64)->nullable()->after('person_id')->index();
            }
            if (!Schema::hasColumn('employees', 'sitms_id')) {
                $table->string('sitms_id', 64)->nullable()->after('sitms_employee_id')->index();
            }
        });

        // Kembalikan data
        DB::statement("UPDATE employees SET sitms_employee_id = COALESCE(sitms_employee_id, employee_id)");
        DB::statement("UPDATE employees SET sitms_id = COALESCE(sitms_id, id_sitms)");

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'employee_id')) $table->dropColumn('employee_id');
            if (Schema::hasColumn('employees', 'id_sitms'))   $table->dropColumn('id_sitms');
        });
    }
};
