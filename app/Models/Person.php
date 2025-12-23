<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Person extends Model
{
    use HasFactory;

    protected $table = 'persons';
    public $incrementing = false; // Karena ID Anda char(26)/UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id', // ID harus fillable jika kita generate manual (lihat boot di bawah)
        'full_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'address', // Alamat KTP
        'city',    // Kota KTP
        'phone',
        'email',
        'nik_hash',
        'nik_last4',
        
        // --- KOLOM TAMBAHAN UNTUK PELAMAR ---
        'nik', // NIK Plain text (opsional, jika dipakai)
        'religion',
        'marital_status',
        'height',
        'weight',
        
        'linkedin_url',
        'instagram_url',
        
        'address_domicile',
        'city_domicile',
        'province_ktp',
        'province_domicile',

        // Data JSON (Repeater)
        'family_data',
        'education_history',
        'work_experience',
        'organization_experience',
        'skills',
        'certifications',

        // Dokumen (Path File)
        'cv_path',
        'photo_path',
        'id_card_path',
        'ijazah_path',
        'transcripts_path',
        'skck_path',
        'health_cert_path',
        'toefl_path',
        'drug_free_path',
        'other_doc_path'
    ];

    protected $hidden = ['nik_hash'];

    protected $appends = ['nik_masked', 'has_nik'];

    protected $casts = [
        'date_of_birth' => 'date',
        // Auto-cast JSON ke Array
        'family_data' => 'array',
        'education_history' => 'array',
        'work_experience' => 'array',
        'organization_experience' => 'array',
        'skills' => 'array',
        'certifications' => 'array',
    ];

    // Boot untuk Auto Generate ID (UUID/ULID) jika kosong
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::ulid(); // Atau Str::uuid() sesuai format char(26) Anda
            }
        });
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'person_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'person_id');
    }

    public function getNikMaskedAttribute(): ?string
    {
        $last4 = $this->attributes['nik_last4'] ?? null;
        if (!$last4) return null;
        return str_repeat('*', 12) . $last4;
    }

    public function getHasNikAttribute(): bool
    {
        return !empty($this->attributes['nik_hash']);
    }
}