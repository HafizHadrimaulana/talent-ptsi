<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SITMS\SitmsClient;
use App\Services\SITMS\HttpSitmsClient;
use Psr\Log\LoggerInterface;

class SitmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SitmsClient::class, HttpSitmsClient::class);
        $this->app->bind(HttpSitmsClient::class, function($app) {
            return new HttpSitmsClient($app->make(LoggerInterface::class));
        });
    }
}