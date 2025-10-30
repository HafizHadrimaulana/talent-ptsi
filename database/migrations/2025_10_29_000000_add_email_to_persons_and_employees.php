<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // persons.email
        if (Schema::hasTable('persons') && !Schema::hasColumn('persons', 'email')) {
            Schema::table('persons', function (Blueprint $table) {
                $table->string('email', 150)->nullable()->after('phone');
                $table->index('email', 'persons_email_idx');
            });
        }

        // employees.email
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'email')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('email', 150)->nullable()->after('company_name');
                $table->index('email', 'employees_email_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('persons') && Schema::hasColumn('persons', 'email')) {
            Schema::table('persons', function (Blueprint $table) {
                $table->dropIndex('persons_email_idx');
                $table->dropColumn('email');
            });
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'email')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex('employees_email_idx');
                $table->dropColumn('email');
            });
        }
    }
};
