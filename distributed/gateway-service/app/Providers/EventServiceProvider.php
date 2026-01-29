<?php

namespace App\Providers;

use App\Services\Events\EventSubscriber;
use App\Services\Events\Handlers\MonitoringEventHandler;
use App\Services\Events\Handlers\CacheInvalidationHandler;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register EventSubscriber as singleton
        $this->app->singleton(EventSubscriber::class, function ($app) {
            return new EventSubscriber();
        });

        // Register MonitoringEventHandler as singleton
        $this->app->singleton(MonitoringEventHandler::class, function ($app) {
            return new MonitoringEventHandler();
        });

        // Register CacheInvalidationHandler as singleton
        $this->app->singleton(CacheInvalidationHandler::class, function ($app) {
            return new CacheInvalidationHandler();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Event handlers are registered through the EventsListenCommand
        // No additional boot logic needed here
    }
}
