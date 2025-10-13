<?php
// app/Providers/SitmsServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SITMS\SitmsClient;
use App\Services\SITMS\HttpSitmsClient;

class SitmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SitmsClient::class, HttpSitmsClient::class);
        $this->app->bind(HttpSitmsClient::class, fn($app) => new HttpSitmsClient($app['log']));
    }
}
