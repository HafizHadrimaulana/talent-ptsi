<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            // objek yang di-approve (recruitment_requests / contracts / documents / job_postings)
            $table->morphs('approvable'); // approvable_type, approvable_id

            // siapa yang meminta & menyetujui
            $table->ulid('requester_person_id')->nullable()->index();
            $table->unsignedBigInteger('requester_user_id')->nullable()->index();

            $table->ulid('approver_person_id')->nullable()->index();
            $table->unsignedBigInteger('approver_user_id')->nullable()->index();

            // status
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected
            $table->text('note')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            // FK aman (nullable)
            $table->foreign('requester_person_id')->references('id')->on('persons')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('approver_person_id')->references('id')->on('persons')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void {
        Schema::dropIfExists('approvals');
    }
};
