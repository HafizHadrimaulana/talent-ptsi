<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // model_has_roles: tambah unit_id (nullable) + index
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_roles', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('model_type')->index();
            }
        });

        // model_has_permissions: opsional (kalau nanti mau scope permission per unit)
        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('model_has_permissions', 'unit_id')) {
                    $table->unsignedBigInteger('unit_id')->nullable()->after('model_type')->index();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (Schema::hasColumn('model_has_roles', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
        });

        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                if (Schema::hasColumn('model_has_permissions', 'unit_id')) {
                    $table->dropColumn('unit_id');
                }
            });
        }
    }
};
