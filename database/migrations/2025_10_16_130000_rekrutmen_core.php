<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {

    // 1) Izin Prinsip / Recruitment Request
    Schema::create('recruitment_requests', function(Blueprint $t){
      $t->id();
      $t->unsignedBigInteger('unit_id')->index();
      $t->string('title');
      $t->string('position');
      $t->unsignedInteger('headcount')->default(1);
      $t->text('justification')->nullable();
      $t->enum('status', ['draft','submitted','approved','rejected'])->default('draft');
      $t->unsignedBigInteger('requested_by')->index();
      $t->unsignedBigInteger('approved_by')->nullable()->index();
      $t->timestamp('approved_at')->nullable();
      $t->json('meta')->nullable();
      $t->timestamps();
    });

    // 2) Applicants (pelamar eksternal / calon)
    Schema::create('applicants', function(Blueprint $t){
      $t->id();
      $t->unsignedBigInteger('unit_id')->index();
      $t->unsignedBigInteger('recruitment_request_id')->nullable()->index();
      $t->string('full_name');
      $t->string('email')->nullable();
      $t->string('phone')->nullable();
      $t->string('nik_number')->nullable();
      $t->string('position_applied')->nullable();
      $t->enum('status',['new','shortlisted','selected','rejected'])->default('new');
      $t->text('notes')->nullable();
      $t->json('attachments')->nullable(); // paths
      $t->timestamps();
    });

    // 3) Approvals (polymorphic, untuk request & kontrak)
    Schema::create('approvals', function(Blueprint $t){
      $t->id();
      $t->morphs('approvable'); // approvable_type, approvable_id
      $t->unsignedTinyInteger('level')->default(1);           // 1=Unit, 2=Corp, dst
      $t->string('role_key')->nullable();                     // 'vp_gm', 'sdm_unit', etc
      $t->unsignedBigInteger('user_id')->nullable()->index(); // kalau fixed user
      $t->enum('status',['pending','approved','rejected'])->default('pending');
      $t->text('note')->nullable();
      $t->timestamp('decided_at')->nullable();
      $t->timestamps();
    });

    // 4) Contracts (SPK/PKWT)
    Schema::create('contracts', function(Blueprint $t){
      $t->id();
      $t->enum('type', ['SPK','PKWT']);
      $t->unsignedBigInteger('unit_id')->index();
      // sumber pihak
      $t->unsignedBigInteger('applicant_id')->nullable()->index(); // eksternal
      $t->unsignedBigInteger('employee_id')->nullable()->index();  // internal (existing)
      // header
      $t->string('person_name');
      $t->string('position');
      $t->date('start_date')->nullable();
      $t->date('end_date')->nullable();
      $t->decimal('salary', 18,2)->nullable();
      $t->json('components')->nullable();       // tunjangan, dll
      $t->enum('status',['draft','review','approved','signed','archived'])->default('draft');
      $t->unsignedBigInteger('created_by')->index();
      $t->unsignedBigInteger('approved_by')->nullable()->index();
      $t->timestamp('approved_at')->nullable();
      $t->string('number')->nullable()->index(); // nomor kontrak
      $t->string('file_path')->nullable();
      $t->json('meta')->nullable();
      $t->timestamps();
    });

    // 5) Signatures (sederhana dulu; nanti bisa diupgrade e-sign)
    Schema::create('contract_signatures', function(Blueprint $t){
      $t->id();
      $t->unsignedBigInteger('contract_id')->index();
      $t->string('signer_role'); // 'candidate','vp_gm','sdm_unit'
      $t->unsignedBigInteger('signer_user_id')->nullable()->index();
      $t->string('signer_name')->nullable();
      $t->string('signer_email')->nullable();
      $t->timestamp('signed_at')->nullable();
      $t->string('ip_address')->nullable();
      $t->json('payload')->nullable();
      $t->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('contract_signatures');
    Schema::dropIfExists('contracts');
    Schema::dropIfExists('approvals');
    Schema::dropIfExists('applicants');
    Schema::dropIfExists('recruitment_requests');
  }
};
