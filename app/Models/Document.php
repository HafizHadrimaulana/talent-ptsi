<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = [
        'doc_type',
        'doc_no',
        'title',
        'storage_disk',
        'path',
        'mime',
        'size_bytes',
        'meta_json',
        'hash_sha256',
        'created_by_person_id',
        'created_by_user_id',
    ];

    protected $casts = [
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
}
