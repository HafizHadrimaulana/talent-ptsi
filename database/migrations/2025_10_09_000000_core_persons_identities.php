<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('persons', function (Blueprint $table) {
            $table->ulid('id')->primary();              // person_id (ULID)
            $table->string('full_name', 200)->index();
            $table->string('gender', 10)->nullable();   // Pria/Wanita
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth', 120)->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('nik_hash', 64)->nullable()->index();
            $table->string('nik_last4', 4)->nullable();
            $table->timestamps();
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->ulid('person_id')->nullable()->index();
            $table->string('email', 191)->index();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::create('identities', function (Blueprint $table) {
            $table->id();
            $table->ulid('person_id')->index();
            $table->string('system', 50)->index();      // SITMS, SAPA, dll
            $table->string('external_id', 128)->index();
            $table->timestamps();

            $table->unique(['system', 'external_id']);
            $table->foreign('person_id')->references('id')->on('persons')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('identities');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('persons');
    }
};
