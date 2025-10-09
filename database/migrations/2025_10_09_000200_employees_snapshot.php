<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id')->unique();
            $t->string('company_name')->default('PT Surveyor Indonesia');
            $t->string('employee_status')->nullable(); // Tetap/PKWT
            $t->foreignId('directorate_id')->nullable()->constrained('directorates')->nullOnDelete();
            $t->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $t->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $t->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $t->foreignId('position_level_id')->nullable()->constrained('position_levels')->nullOnDelete();
            $t->string('talent_class_level')->nullable();
            $t->boolean('is_active')->default(true);
            $t->string('sitms_employee_id',64)->nullable(); // "1913793"
            $t->string('sitms_id',64)->nullable();          // "824"
            $t->string('home_base_raw')->nullable();
            $t->string('home_base_city')->nullable();
            $t->string('home_base_province')->nullable();
            $t->date('latest_jobs_start_date')->nullable();
            $t->string('latest_jobs_unit')->nullable();
            $t->string('latest_jobs_title')->nullable();
            $t->timestamps();
            $t->index(['unit_id','position_level_id','is_active']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('employees');
    }
};
