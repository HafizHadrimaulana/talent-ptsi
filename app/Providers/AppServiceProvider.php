<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\SITMS\SitmsClient::class, function(){
            return config('sitms.read_enabled')
                ? new \App\Services\SITMS\HttpSitmsClient
                : new \App\Services\SITMS\NullSitmsClient;
        });
    }

    public function boot(): void {}
}
