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
        Schema::table('training_documents', function (Blueprint $table) {
            // Hapus kolom draft_path
            $table->dropColumn('draft_path');

            // Tambah kolom baru
            $table->string('signed_face_path')->nullable()->after('payload');
            $table->string('signed_signature_path')->nullable()->after('signed_face_path');
            $table->string('signed_location')->nullable()->after('signed_signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_documents', function (Blueprint $table) {
            // Tambahkan kembali draft_path
            $table->string('draft_path')->nullable()->after('payload');

            // Hapus kolom baru
            $table->dropColumn(['signed_face_path', 'signed_signature_path', 'signed_location']);
        });
    }
};
