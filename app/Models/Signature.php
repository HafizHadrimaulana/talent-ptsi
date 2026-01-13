<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    protected $table = 'signatures';

    protected $fillable = [
        'document_id',
        'signer_person_id',
        'signer_user_id',
        'signer_role',
        'signature_draw_data',
        'signature_draw_hash',
        'camera_photo_path',
        'camera_photo_hash',
        'geo_lat',
        'geo_lng',
        'geo_accuracy_m',
        'signed_at',
        'ip_address',
        'verification_code'
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Relasi ke tabel Users (Signer)
     */
    public function signerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }

    /**
     * Relasi ke tabel Persons (Signer) - INI YANG MEMPERBAIKI ERROR UTAMA
     */
    public function signerPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'signer_person_id');
    }
}