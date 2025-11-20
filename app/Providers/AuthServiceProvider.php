<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        // Kalau suatu saat kamu pakai Policy, ini yang ngedaftarin
        $this->registerPolicies();

        // SUPERADMIN: auto boleh semua ability / permission
        Gate::before(function ($user, $ability) {
            return $user && $user->hasRole('Superadmin') ? true : null;
        });
    }
}
