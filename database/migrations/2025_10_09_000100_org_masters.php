<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // ---- directorates (buat kalau belum ada) ----
        if (!Schema::hasTable('directorates')) {
            Schema::create('directorates', function (Blueprint $t) {
                $t->id();
                $t->string('code',32)->nullable();
                $t->string('name');
                $t->timestamps();
            });
        }

        // ---- units (jangan bikin lagi; cukup tambah kolom yg kurang) ----
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $t) {
                $t->id();
                $t->string('code',32)->nullable(); // existing kamu varchar(30) NOT NULL, biarin aja
                $t->string('name');
                $t->foreignId('directorate_id')->nullable()->constrained('directorates')->nullOnDelete();
                $t->timestamps();
                $t->index('name');
            });
        } else {
            // Tambah kolom directorate_id kalau belum ada
            if (!Schema::hasColumn('units','directorate_id')) {
                Schema::table('units', function (Blueprint $t) {
                    $t->foreignId('directorate_id')->nullable()->after('name');
                });
                // Pasang FK kalau tabel directorates ada
                if (Schema::hasTable('directorates')) {
                    Schema::table('units', function (Blueprint $t) {
                        $t->foreign('directorate_id')->references('id')->on('directorates')->nullOnDelete();
                    });
                }
            }
            // created_at/updated_at kalau belum ada
            if (!Schema::hasColumn('units','created_at')) {
                Schema::table('units', fn(Blueprint $t) => $t->timestamps());
            }
            // index name kalau belum
            // (MySQL gak punya "hasIndex" native; aman dilewati)
        }

        // ---- locations ----
        if (!Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('type',32)->nullable(); // Head Office / Branch Office
                $t->string('city')->nullable();
                $t->string('province')->nullable();
                $t->timestamps();
            });
        } else {
            if (!Schema::hasColumn('locations','type')) Schema::table('locations', fn(Blueprint $t)=>$t->string('type',32)->nullable()->after('name'));
            if (!Schema::hasColumn('locations','city')) Schema::table('locations', fn(Blueprint $t)=>$t->string('city')->nullable());
            if (!Schema::hasColumn('locations','province')) Schema::table('locations', fn(Blueprint $t)=>$t->string('province')->nullable());
            if (!Schema::hasColumn('locations','created_at')) Schema::table('locations', fn(Blueprint $t)=>$t->timestamps());
        }

        // ---- position_levels ----
        if (!Schema::hasTable('position_levels')) {
            Schema::create('position_levels', function (Blueprint $t) {
                $t->id();
                $t->string('code',32)->nullable(); // contoh: BOD-4
                $t->string('name');
                $t->timestamps();
            });
        } else {
            if (!Schema::hasColumn('position_levels','code')) Schema::table('position_levels', fn(Blueprint $t)=>$t->string('code',32)->nullable()->after('id'));
            if (!Schema::hasColumn('position_levels','created_at')) Schema::table('position_levels', fn(Blueprint $t)=>$t->timestamps());
        }

        // ---- positions ----
        if (!Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index('name');
            });
        } else {
            if (!Schema::hasColumn('positions','is_active')) Schema::table('positions', fn(Blueprint $t)=>$t->boolean('is_active')->default(true)->after('name'));
            if (!Schema::hasColumn('positions','created_at')) Schema::table('positions', fn(Blueprint $t)=>$t->timestamps());
        }
    }

    public function down(): void {
        // Jangan drop tabel existing hasil import lama
        // Biarkan kosong / atau hanya drop tabel yang kita buat kalau mau
    }
};
