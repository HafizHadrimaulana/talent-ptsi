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
            // Tambahkan kolom yang hilang
            if (!Schema::hasColumn('applicants', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('applicants', 'recruitment_request_id')) {
                $table->foreignId('recruitment_request_id')->after('unit_id')->constrained('recruitment_requests')->onDelete('cascade');
            }
            if (!Schema::hasColumn('applicants', 'email')) {
                $table->string('email')->after('full_name');
            }
            if (!Schema::hasColumn('applicants', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('applicants', 'nik_number')) {
                $table->string('nik_number')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('applicants', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('applicants', 'attachments')) {
                $table->json('attachments')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            //
        });
    }
};
