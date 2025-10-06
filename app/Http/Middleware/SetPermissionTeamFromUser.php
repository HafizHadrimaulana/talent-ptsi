<?php
// app/Http/Middleware/SetPermissionTeamFromUser.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionTeamFromUser
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->unit_id);
        }
        return $next($request);
    }
}
