<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // ===== Recruitment Requests (izin prinsip / permintaan rekrutmen)
        Schema::create('recruitment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 50)->nullable()->unique();
            $table->unsignedBigInteger('unit_id')->nullable()->index();
            $table->unsignedBigInteger('position_id')->nullable()->index();
            $table->unsignedBigInteger('position_level_id')->nullable()->index();
            $table->integer('headcount')->default(1);
            $table->string('employment_type', 40)->nullable(); // PKWT/PKWTT/Intern
            $table->text('justification')->nullable();
            $table->date('target_start_date')->nullable();
            $table->string('status', 30)->default('draft')->index(); // draft|submitted|approved|rejected|published|closed

            // publish flags (di-migrate lagi di file add_publish_fields)
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->ulid('requested_by_person_id')->nullable()->index();
            $table->unsignedBigInteger('requested_by_user_id')->nullable()->index();

            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('position_level_id')->references('id')->on('position_levels')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('requested_by_person_id')->references('id')->on('persons')->nullOnDelete()->cascadeOnUpdate();
        });

        // ===== Contracts (SPK/PKWT) — satu meja untuk dua template
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_no', 80)->nullable()->unique();   // SPKXXX/... atau PERJXXX/...
            $table->string('contract_type', 20)->index();              // SPK | PKWT

            // subjek kontrak
            $table->ulid('person_id')->nullable()->index();            // calon/pegawai
            $table->string('employee_id', 64)->nullable()->index();    // jika sudah terdaftar
            $table->unsignedBigInteger('unit_id')->nullable()->index();
            $table->unsignedBigInteger('position_id')->nullable()->index();
            $table->unsignedBigInteger('position_level_id')->nullable()->index();

            // periode
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // e-sign requirement flags
            $table->boolean('requires_draw_signature')->default(true);
            $table->boolean('requires_camera')->default(true);
            $table->boolean('requires_geolocation')->default(true);

            // status
            $table->string('status', 30)->default('draft')->index(); // draft|waiting_signature|signed|rejected|void

            // dokumen jadi (PDF) — relasi ke documents.id
            $table->unsignedBigInteger('document_id')->nullable()->index();

            // audit
            $table->ulid('created_by_person_id')->nullable()->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('position_level_id')->references('id')->on('position_levels')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('document_id')->references('id')->on('documents')->nullOnDelete()->cascadeOnUpdate();
        });

        // ===== Signatures (e-sign: drawing + camera + geolocation)
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id')->index();        // dokumen yang ditandatangani (PDF)
            $table->string('signer_role', 40)->index();                 // candidate|employee|approver|witness
            $table->ulid('signer_person_id')->nullable()->index();
            $table->unsignedBigInteger('signer_user_id')->nullable()->index();

            // draw signature (data image b64/ path file)
            $table->text('signature_draw_data')->nullable();            // dataURL atau path
            $table->string('signature_draw_hash', 64)->nullable()->index();

            // camera evidence
            $table->string('camera_photo_path', 255)->nullable();
            $table->string('camera_photo_hash', 64)->nullable()->index();

            // geolocation
            $table->decimal('geo_lat', 11, 7)->nullable();
            $table->decimal('geo_lng', 11, 7)->nullable();
            $table->string('geo_accuracy_m', 20)->nullable();
            $table->timestamp('signed_at')->nullable();

            // verification/badge
            $table->string('verification_code', 50)->nullable()->index();

            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('signer_person_id')->references('id')->on('persons')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('signatures');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('recruitment_requests');
    }
};
