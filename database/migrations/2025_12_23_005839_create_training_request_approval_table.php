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
        Schema::create('training_request_approval', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('training_request_id');
            $table->unsignedBigInteger('user_id');

            $table->string('role');
            $table->string('action'); // approve | reject
            $table->string('from_status');
            $table->string('to_status');

            $table->text('note')->nullable();

            $table->foreign('training_request_id')->references('id')->on('training_request')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_request_approval');
    }
};
