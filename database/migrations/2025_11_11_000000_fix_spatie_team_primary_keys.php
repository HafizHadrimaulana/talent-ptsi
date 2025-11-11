<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Matikan FK checks sementara (session-level)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // =========================
        // MODEL_HAS_ROLES (REBUILD)
        // =========================

        // Backfill unit_id dari users bila NULL (user)
        DB::statement("
            UPDATE model_has_roles mhr
            JOIN users u ON u.id = mhr.model_id
            SET mhr.unit_id = u.unit_id
            WHERE mhr.model_type = 'App\\\\Models\\\\User' AND mhr.unit_id IS NULL
        ");
        // Sapu sisa NULL → 0 (global scope)
        DB::statement("UPDATE model_has_roles SET unit_id = 0 WHERE unit_id IS NULL");

        // Buat tabel baru dengan PK (role_id, model_id, model_type, unit_id)
        DB::statement("
            CREATE TABLE model_has_roles_new (
                role_id   BIGINT UNSIGNED NOT NULL,
                model_type VARCHAR(255) NOT NULL,
                model_id  BIGINT UNSIGNED NOT NULL,
                unit_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (role_id, model_id, model_type, unit_id),
                KEY mhr_model_type_unit_idx (model_id, model_type, unit_id),
                KEY mhr_role_id_idx (role_id),
                CONSTRAINT mhr_role_id_fk FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Copy data ke tabel baru 1:1
        DB::statement("
            INSERT IGNORE INTO model_has_roles_new (role_id, model_type, model_id, unit_id)
            SELECT role_id, model_type, model_id, COALESCE(unit_id,0) FROM model_has_roles
        ");

        // Drop tabel lama & rename
        DB::statement("DROP TABLE model_has_roles");
        DB::statement("RENAME TABLE model_has_roles_new TO model_has_roles");

        // ==============================
        // MODEL_HAS_PERMISSIONS (REBUILD)
        // ==============================

        // Backfill unit_id dari users bila NULL (user)
        DB::statement("
            UPDATE model_has_permissions mhp
            JOIN users u ON u.id = mhp.model_id
            SET mhp.unit_id = u.unit_id
            WHERE mhp.model_type = 'App\\\\Models\\\\User' AND mhp.unit_id IS NULL
        ");
        DB::statement("UPDATE model_has_permissions SET unit_id = 0 WHERE unit_id IS NULL");

        // Buat tabel baru dengan PK (permission_id, model_id, model_type, unit_id)
        DB::statement("
            CREATE TABLE model_has_permissions_new (
                permission_id BIGINT UNSIGNED NOT NULL,
                model_type    VARCHAR(255) NOT NULL,
                model_id      BIGINT UNSIGNED NOT NULL,
                unit_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (permission_id, model_id, model_type, unit_id),
                KEY mhp_model_type_unit_idx (model_id, model_type, unit_id),
                KEY mhp_permission_id_idx (permission_id),
                CONSTRAINT mhp_permission_id_fk FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        DB::statement("
            INSERT IGNORE INTO model_has_permissions_new (permission_id, model_type, model_id, unit_id)
            SELECT permission_id, model_type, model_id, COALESCE(unit_id,0) FROM model_has_permissions
        ");

        DB::statement("DROP TABLE model_has_permissions");
        DB::statement("RENAME TABLE model_has_permissions_new TO model_has_permissions");

        // Nyalakan lagi FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Kembalikan ke PK lama tanpa unit_id (Spatie default)

        // Roles
        DB::statement("
            CREATE TABLE model_has_roles_old (
                role_id   BIGINT UNSIGNED NOT NULL,
                model_type VARCHAR(255) NOT NULL,
                model_id  BIGINT UNSIGNED NOT NULL,
                unit_id   BIGINT UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (role_id, model_id, model_type),
                KEY mhr_model_type_idx (model_id, model_type),
                KEY mhr_role_id_idx (role_id),
                CONSTRAINT mhr_role_id_fk_old FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        DB::statement("
            INSERT IGNORE INTO model_has_roles_old (role_id, model_type, model_id, unit_id)
            SELECT role_id, model_type, model_id, NULLIF(unit_id,0) FROM model_has_roles
        ");
        DB::statement("DROP TABLE model_has_roles");
        DB::statement("RENAME TABLE model_has_roles_old TO model_has_roles");

        // Permissions
        DB::statement("
            CREATE TABLE model_has_permissions_old (
                permission_id BIGINT UNSIGNED NOT NULL,
                model_type    VARCHAR(255) NOT NULL,
                model_id      BIGINT UNSIGNED NOT NULL,
                unit_id       BIGINT UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (permission_id, model_id, model_type),
                KEY mhp_model_type_idx (model_id, model_type),
                KEY mhp_permission_id_idx (permission_id),
                CONSTRAINT mhp_permission_id_fk_old FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        DB::statement("
            INSERT IGNORE INTO model_has_permissions_old (permission_id, model_type, model_id, unit_id)
            SELECT permission_id, model_type, model_id, NULLIF(unit_id,0) FROM model_has_permissions
        ");
        DB::statement("DROP TABLE model_has_permissions");
        DB::statement("RENAME TABLE model_has_permissions_old TO model_has_permissions");

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
