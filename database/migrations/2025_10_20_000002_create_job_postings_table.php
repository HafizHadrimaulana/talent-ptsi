<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('job_postings', function (Blueprint $t) {
      $t->id();
      $t->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
      $t->string('slug')->unique();
      $t->boolean('is_active')->default(true)->index();
      $t->timestamp('published_at')->nullable();
      $t->timestamp('closed_at')->nullable();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('job_postings'); }
};
