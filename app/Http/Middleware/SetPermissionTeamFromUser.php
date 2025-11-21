<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionTeamFromUser
{
    public function handle(Request $request, Closure $next)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($user) {
            // Pakai unit_id user sebagai team id
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->unit_id);
        } else {
            // Guest: clear team id (optional, biar bersih)
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }

        return $next($request);
    }
}
