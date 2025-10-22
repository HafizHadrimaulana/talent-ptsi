<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('person_id')->nullable()->index();
            $table->string('employee_id', 64)->nullable()->index();
            $table->string('doc_type', 40)->index();           // SPK | PKWT | OTHER
            $table->string('storage_disk', 40)->default('local');
            $table->string('path', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('hash_sha256', 64)->nullable()->index();
            $table->json('meta')->nullable();                   // barcode/verification number, template vars, dll
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('documents');
    }
};
