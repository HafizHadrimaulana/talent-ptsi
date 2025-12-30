<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('applicants', function (Blueprint $table) {
            // 1. Tambahkan user_id (Nullable karena mungkin ada pelamar tamu/guest, diletakkan setelah id)
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');

            // 2. Tambahkan recruitment_request_id (PENTING untuk relasi ke lowongan)
            $table->foreignId('recruitment_request_id')->after('unit_id')->constrained('recruitment_requests')->onDelete('cascade');

            // 3. Tambahkan kolom data diri lain yang kurang
            $table->string('email')->after('full_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('nik_number')->nullable()->after('phone');
            
            // 4. Tambahkan notes dan attachments
            $table->text('notes')->nullable()->after('status');
            $table->json('attachments')->nullable()->after('notes'); // Simpan path CV/Cover letter dalam format JSON
        });
    }

    public function down()
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'recruitment_request_id', 'email', 'phone', 'nik_number', 'notes', 'attachments']);
        });
    }
};
