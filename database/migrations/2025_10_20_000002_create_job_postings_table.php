<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recruitment_request_id')->nullable()->index();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('location_text', 150)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('recruitment_request_id')->references('id')->on('recruitment_requests')
                ->cascadeOnUpdate()->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('job_postings');
    }
};
