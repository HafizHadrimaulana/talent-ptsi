<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('persons', function (Blueprint $t) {
            $t->ulid('id')->primary();
            $t->string('full_name');
            $t->enum('gender',["Pria","Wanita"])->nullable();
            $t->date('date_of_birth')->nullable();
            $t->string('place_of_birth')->nullable();
            $t->string('nik_hash',191)->nullable()->unique();
            $t->string('nik_last4',4)->nullable();
            $t->string('phone',32)->nullable();
            $t->timestamps();
            $t->index('full_name');
        });

        Schema::create('emails', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('email');
            $t->boolean('is_primary')->default(false);
            $t->boolean('is_verified')->default(false);
            $t->timestamps();
            $t->unique(['person_id','email']);
        });

        Schema::create('identities', function (Blueprint $t) {
            $t->id();
            $t->ulid('person_id');
            $t->string('system',32);      // 'SITMS'
            $t->string('external_id',64); // id_sitms / employee_id
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->unique(['system','external_id']);
            $t->index(['person_id','system']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('identities');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('persons');
    }
};
