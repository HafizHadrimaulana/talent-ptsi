<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function indexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$db, $table, $index]
        );
        return !empty($rows);
    }

    public function up(): void
    {
        // 1) tambah kolom unit_id kalau belum ada
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('guard_name')->index();
            }
        });

        // 2) drop index lama HANYA kalau ada
        if ($this->indexExists('roles', 'roles_name_unique')) {
            DB::statement('ALTER TABLE `roles` DROP INDEX `roles_name_unique`');
        }
        if ($this->indexExists('roles', 'roles_name_guard_name_unique')) {
            DB::statement('ALTER TABLE `roles` DROP INDEX `roles_name_guard_name_unique`');
        }
        if ($this->indexExists('roles', 'roles_name_guard_name_unit_id_unique')) {
            DB::statement('ALTER TABLE `roles` DROP INDEX `roles_name_guard_name_unit_id_unique`');
        }

        // 3) buat composite unique baru kalau belum ada
        if (!$this->indexExists('roles', 'roles_name_guard_name_unit_id_unique')) {
            DB::statement('ALTER TABLE `roles` ADD UNIQUE `roles_name_guard_name_unit_id_unique` (`name`, `guard_name`, `unit_id`)');
        }

        // 4) pasang FK ke units kalau tabelnya ada (opsional & aman)
        if (Schema::hasTable('units')) {
            // drop FK lama jika ada namanya generik (optional best-effort)
            try { DB::statement('ALTER TABLE `roles` DROP FOREIGN KEY `roles_unit_id_foreign`'); } catch (\Throwable $e) {}
            Schema::table('roles', function (Blueprint $table) {
                $table->foreign('unit_id')->references('id')->on('units')->cascadeOnUpdate()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // rollback: drop FK, drop unique, drop col
        try { DB::statement('ALTER TABLE `roles` DROP FOREIGN KEY `roles_unit_id_foreign`'); } catch (\Throwable $e) {}
        if ($this->indexExists('roles', 'roles_name_guard_name_unit_id_unique')) {
            DB::statement('ALTER TABLE `roles` DROP INDEX `roles_name_guard_name_unit_id_unique`');
        }
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
        });
    }
};
