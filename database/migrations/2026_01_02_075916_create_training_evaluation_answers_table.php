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
        Schema::create('training_evaluation_answers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_request_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('user_id');

            $table->integer('score')->nullable();
            $table->text('text_answer')->nullable();
            
            $table->timestamps();

            $table->foreign('training_request_id')
                ->references('id')->on('training_request')
                ->cascadeOnDelete();

            $table->foreign('question_id')
                ->references('id')->on('training_evaluation_questions')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_evaluation_answers');
    }
};
