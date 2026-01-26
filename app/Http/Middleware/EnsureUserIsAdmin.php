<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $allowedRoles = [
            'Superadmin',
            'DHC',
            'SDM Unit',
            'Dir SDM',
            'Kepala Unit',
            'Admin Operasi Unit',
            'Kepala Proyek (MP)',
            'AVP',
        ];

        if (!Auth::user()->hasAnyRole($allowedRoles)) {
            abort(403, 'UNAUTHORIZED ACCESS');
        }

        return $next($request);
    }
}