<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Event Service Provider
 * 
 * Event handlers are now registered via config/events.php
 * and loaded automatically by ProcessEventJob
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Event handlers are registered via config/events.php
        // and instantiated by ProcessEventJob when needed
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }
}
