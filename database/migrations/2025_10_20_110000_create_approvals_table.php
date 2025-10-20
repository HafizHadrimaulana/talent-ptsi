<?php

// database/migrations/2025_10_20_110000_create_approvals_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('approvals', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('approvable_id');
      $t->string('approvable_type');
      $t->unsignedTinyInteger('level')->default(1);
      $t->string('role_key')->nullable();
      $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
      $t->enum('status',['pending','approved','rejected'])->default('pending')->index();
      $t->text('note')->nullable();
      $t->timestamp('decided_at')->nullable();
      $t->timestamps();

      $t->index(['approvable_type','approvable_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('approvals'); }
};
