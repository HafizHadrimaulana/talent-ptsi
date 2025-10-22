<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->ulid('person_id')->index();

            // === SITMS naming ===
            $table->string('employee_id', 64)->index();      // kunci utama dari SITMS
            $table->string('id_sitms', 64)->nullable()->index();

            // === legacy alias (untuk transisi; boleh di-drop belakangan) ===
            $table->string('sitms_employee_id', 64)->nullable()->index();
            $table->string('sitms_id', 64)->nullable()->index();

            // data pekerjaan
            $table->string('company_name', 150)->default('PT Surveyor Indonesia');
            $table->string('employee_status', 50)->nullable(); // PKWTT/PKWT/Outsourced/etc
            $table->unsignedBigInteger('directorate_id')->nullable()->index();
            $table->unsignedBigInteger('unit_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('position_id')->nullable()->index();
            $table->unsignedBigInteger('position_level_id')->nullable()->index();
            $table->string('talent_class_level', 30)->nullable();
            $table->boolean('is_active')->default(true)->index();

            // lokasi kerja (raw + normalisasi)
            $table->string('home_base_raw', 150)->nullable();
            $table->string('home_base_city', 120)->nullable();
            $table->string('home_base_province', 120)->nullable();

            // latest jobs (DWH-lite)
            $table->date('latest_jobs_start_date')->nullable();
            $table->string('latest_jobs_unit', 150)->nullable();
            $table->string('latest_jobs_title', 150)->nullable();

            $table->timestamps();

            $table->unique('employee_id'); // diharapkan unik
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('directorate_id')->references('id')->on('directorates')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('position_level_id')->references('id')->on('position_levels')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('employees');
    }
};
