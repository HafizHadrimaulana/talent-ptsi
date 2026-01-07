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
        Schema::create('recruitment_applicants', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel recruitment_requests
            $table->unsignedBigInteger('recruitment_request_id'); 
            
            // Relasi ke tabel users
            $table->unsignedBigInteger('user_id')->nullable();
            
            $table->string('position_applied')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('university')->nullable();
            $table->string('major')->nullable();
            $table->string('cv_path')->nullable();
            
            // Default status biasanya 'Screening CV'
            $table->string('status')->default('Screening CV');
            
            // Kolom tambahan yang sebelumnya hilang
            $table->dateTime('interview_schedule')->nullable();
            $table->text('hr_notes')->nullable();
            
            $table->timestamps();

            // Opsional: Tambahkan index untuk mempercepat pencarian
            $table->index('recruitment_request_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_applicants');
    }
};