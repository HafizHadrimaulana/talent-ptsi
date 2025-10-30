<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interface -> implementation, passing the logger as required by HttpSitmsClient
        $this->app->bind(\App\Services\SITMS\SitmsClient::class, function ($app) {
            /** @var LoggerInterface $logger */
            $logger = $app->make(LoggerInterface::class);

            if (config('sitms.read_enabled')) {
                // HttpSitmsClient constructor expects $log
                return new \App\Services\SITMS\HttpSitmsClient($logger);
            }

            return new \App\Services\SITMS\NullSitmsClient();
        });
    }

    public function boot(): void {}
}
