<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recruitment_applicants', function (Blueprint $table) {
            $table->id();

            // RELASI KE IZIN PRINSIP (recruitment_requests)
            // Menggunakan ID sebagai pengait relasi (Best Practice), 
            // tapi nanti kita tampilkan Ticket Number di View.
            $table->unsignedBigInteger('recruitment_request_id'); 

            $table->foreign('recruitment_request_id')
                  ->references('id')
                  ->on('recruitment_requests') // Sesuai nama tabel fisik di DB
                  ->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable(); // Jika pelamar sudah login

            // Data Diri Pelamar
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('university');
            $table->string('major'); // Jurusan
            $table->string('cv_path'); // Lokasi file upload

            // Tracking Status Lamaran
            // Status: 'Screening', 'Interview HR', 'Interview User', 'Passed', 'Rejected'
            $table->string('status')->default('Screening'); 
            $table->dateTime('interview_schedule')->nullable();
            $table->text('hr_notes')->nullable(); // Catatan DHC

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('recruitment_applicants');
    }
};