<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ==== model_has_roles ====
        if (Schema::hasTable('model_has_roles')) {
            // Drop PK lama
            DB::statement('ALTER TABLE model_has_roles DROP PRIMARY KEY');

            // Tambah PK baru include unit_id
            DB::statement('ALTER TABLE model_has_roles ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`,`unit_id`)');

            // Index unit_id (kalau belum ada, tidak masalah kalau dobel nama; silakan ganti)
            try {
                DB::statement('CREATE INDEX model_has_roles_unit_id_index ON model_has_roles (unit_id)');
            } catch (\Throwable $e) {
                // ignore jika sudah ada
            }
        }

        // ==== model_has_permissions ====
        if (Schema::hasTable('model_has_permissions')) {
            DB::statement('ALTER TABLE model_has_permissions DROP PRIMARY KEY');
            DB::statement('ALTER TABLE model_has_permissions ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`,`unit_id`)');
            try {
                DB::statement('CREATE INDEX model_has_permissions_unit_id_index ON model_has_permissions (unit_id)');
            } catch (\Throwable $e) {
                // ignore jika sudah ada
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('model_has_roles')) {
            DB::statement('ALTER TABLE model_has_roles DROP PRIMARY KEY');
            DB::statement('ALTER TABLE model_has_roles ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`)');
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::statement('ALTER TABLE model_has_permissions DROP PRIMARY KEY');
            DB::statement('ALTER TABLE model_has_permissions ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`)');
        }
    }
};
