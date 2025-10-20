<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('recruitment_requests', function (Blueprint $t) {
      $t->boolean('is_published')->default(false)->index();
      $t->string('slug')->nullable()->unique();
      $t->timestamp('published_at')->nullable();
      $t->string('work_location')->nullable();
      $t->string('employment_type')->nullable(); // full-time, contract, intern, etc
      $t->json('requirements')->nullable();
    });
  }
  public function down(): void {
    Schema::table('recruitment_requests', function (Blueprint $t) {
      $t->dropColumn(['is_published','slug','published_at','work_location','employment_type','requirements']);
    });
  }
};
