<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function fkExists(string $table, string $fkName): bool
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY" LIMIT 1',
            [$db, $table, $fkName]
        );
        return !empty($rows);
    }

    public function up(): void
    {
        // Tambah kolom users.unit_id kalau belum ada
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('password')->index();
            }
        });

        // Pasang FK hanya kalau tabel units ada dan FK belum ada
        if (Schema::hasTable('units')) {
            $fkName = 'users_unit_id_foreign';
            if (!$this->fkExists('users', $fkName)) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('unit_id', 'users_unit_id_foreign')
                          ->references('id')->on('units')
                          ->cascadeOnUpdate()->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Lepas FK jika ada
        $fkName = 'users_unit_id_foreign';
        try {
            if ($this->fkExists('users', $fkName)) {
                Schema::table('users', function (Blueprint $table) use ($fkName) {
                    $table->dropForeign($fkName);
                });
            }
        } catch (\Throwable $e) {}

        // Drop kolom
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
        });
    }
};
