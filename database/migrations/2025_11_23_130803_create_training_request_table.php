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
        Schema::create('training_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_reference_id');
            $table->unsignedBigInteger('employee_id');

            $table->string('status_approval_training')->default('created');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('realisasi_biaya_pelatihan', 15, 2)->nullable();
            $table->decimal('estimasi_total_biaya', 15, 2)->nullable();

            $table->string('lampiran_penawaran')->nullable();
            
            $table->foreign('training_reference_id')->references('id')->on('training_references')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_request');
    }
};
