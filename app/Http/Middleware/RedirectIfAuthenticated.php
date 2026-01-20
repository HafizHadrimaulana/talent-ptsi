<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                if ($user->hasRole('Pelamar')) {
                    return redirect()->route('recruitment.applicant-data.index');
                }

                if ($user->hasAnyRole(['Superadmin', 'DHC', 'SDM Unit', 'Admin Operasi Unit'])) {
                    return redirect()->route('admin.dashboard');
                }

                if ($user->hasAnyRole(['Kepala Unit', 'Dir SDM', 'AVP', 'Kepala Proyek (MP)', 'DBS Unit'])) {
                    return redirect()->route('recruitment.principal-approval.index');
                }

                return redirect()->route('employee.dashboard');
            }
        }

        return $next($request);
    }
}