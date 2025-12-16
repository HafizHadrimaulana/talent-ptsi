<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = [
        'doc_type',
        'doc_no',
        'title',
        'storage_disk',
        'path',
        'original_name',
        'mime',
        'size_bytes',
        'meta',        // Digunakan oleh ContractController
        'meta_json',   // Cadangan jika schema DB menggunakan ini
        'hash_sha256',
        'person_id',   // WAJIB: Untuk relasi ke pemilik dokumen
        'employee_id', // WAJIB: Untuk relasi ke data pegawai
        'created_by_person_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'meta'      => 'array',
        'meta_json' => 'array',
    ];

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'document_id');
    }
}