<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // pastikan migration roles sudah ada kolom unit_id (nullable)
    protected $guarded = [];
}
