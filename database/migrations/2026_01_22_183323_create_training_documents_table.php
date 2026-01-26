<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('training_documents', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_request_id');

            $table->string('template_code');

            // snapshot data
            $table->json('payload');

            // file hasil
            $table->string('draft_path')->nullable();
            $table->string('signed_path')->nullable();

            // tanda tangan
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by')->nullable();

            $table->enum('status', ['draft', 'signed', 'cancelled'])
                ->default('draft');

            $table->foreign('training_request_id')
                ->references('id')->on('training_request')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_documents');
    }
};
