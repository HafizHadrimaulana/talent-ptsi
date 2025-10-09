<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('educations', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('level');
            $t->string('institution');
            $t->string('major')->nullable();
            $t->string('graduation_year',4)->nullable();
            $t->timestamps();
            $t->index(['person_id','level']);
        });
        Schema::create('trainings', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('name');
            $t->string('organizer')->nullable();
            $t->string('year',4)->nullable();
            $t->string('level')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
            $t->index(['person_id','year']);
        });
        Schema::create('certifications', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('name');
            $t->string('organizer')->nullable();
            $t->string('level')->nullable();
            $t->string('certificate_number')->nullable();
            $t->date('issued_date')->nullable();
            $t->date('due_date')->nullable();
            $t->timestamps();
            $t->index(['person_id','due_date']);
        });
        Schema::create('job_histories', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('company');
            $t->string('unit_name')->nullable();
            $t->string('title')->nullable();
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->timestamps();
            $t->index(['person_id','start_date']);
        });
        Schema::create('assignments', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('title');
            $t->string('company')->nullable();
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->string('period_text')->nullable();
            $t->longText('description')->nullable();
            $t->timestamps();
            $t->index('person_id');
        });
        Schema::create('taskforces', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('type')->nullable();
            $t->string('company')->nullable();
            $t->string('name');
            $t->integer('year_start')->nullable();
            $t->integer('year_end')->nullable();
            $t->string('position')->nullable();
            $t->longText('desc')->nullable();
            $t->timestamps();
            $t->index(['person_id','year_start']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('taskforces');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('job_histories');
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('educations');
    }
};
