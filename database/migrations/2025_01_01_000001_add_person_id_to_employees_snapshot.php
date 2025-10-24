<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees_snapshot')) return;

        Schema::table('employees_snapshot', function (Blueprint $t) {
            if (!Schema::hasColumn('employees_snapshot', 'person_id')) {
                // ULID 26 chars — samain dengan persons.id
                $t->char('person_id', 26)->nullable()->after('id');
            }
        });

        // index biar lookup cepat (jangan unique dulu supaya aman kalau ada data lama)
        Schema::table('employees_snapshot', function (Blueprint $t) {
            $t->index('person_id', 'idx_emp_snap_person');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('employees_snapshot')) return;

        Schema::table('employees_snapshot', function (Blueprint $t) {
            $t->dropIndex('idx_emp_snap_person');
            // $t->dropColumn('person_id'); // opsional
        });
    }
};
