<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            // Data Diri Tambahan
            // Kolom 'nik_hash' sudah ada, kita butuh 'nik' biasa untuk display (jika kebijakan boleh menyimpan NIK plain)
            // Jika Anda ingin pakai 'nik' plain text:
            if (!Schema::hasColumn('persons', 'nik')) $table->string('nik', 20)->nullable()->after('id');
            
            if (!Schema::hasColumn('persons', 'religion')) $table->string('religion')->nullable();
            if (!Schema::hasColumn('persons', 'marital_status')) $table->string('marital_status')->nullable();
            if (!Schema::hasColumn('persons', 'height')) $table->integer('height')->nullable(); 
            if (!Schema::hasColumn('persons', 'weight')) $table->integer('weight')->nullable(); 
            
            // Sosmed
            $table->string('linkedin_url')->nullable();
            $table->string('instagram_url')->nullable();

            // Alamat Domisili (Alamat KTP pakai 'address' & 'city' bawaan)
            $table->text('address_domicile')->nullable();
            $table->string('city_domicile')->nullable();
            $table->string('province_ktp')->nullable();
            $table->string('province_domicile')->nullable();

            // Data JSON (Repeater)
            $table->json('family_data')->nullable();
            $table->json('education_history')->nullable(); 
            $table->json('work_experience')->nullable();
            $table->json('organization_experience')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();

            // Dokumen (Path File)
            $table->string('cv_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('id_card_path')->nullable(); 
            $table->string('ijazah_path')->nullable();
            $table->string('transcripts_path')->nullable();
            $table->string('skck_path')->nullable();
            $table->string('health_cert_path')->nullable();
            $table->string('toefl_path')->nullable();
            $table->string('drug_free_path')->nullable();
            $table->string('other_doc_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn([
                'nik', 'religion', 'marital_status', 'height', 'weight',
                'linkedin_url', 'instagram_url', 'address_domicile', 'city_domicile', 'province_ktp', 'province_domicile',
                'family_data', 'education_history', 'work_experience', 'organization_experience', 'skills', 'certifications',
                'cv_path', 'photo_path', 'id_card_path', 'ijazah_path', 'transcripts_path', 'skck_path', 'health_cert_path', 'toefl_path', 'drug_free_path', 'other_doc_path'
            ]);
        });
    }
};