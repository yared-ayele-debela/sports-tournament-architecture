<?php

namespace App\Providers;

use App\Services\Events\EventPublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Services\Events\Handlers\TournamentEventHandler;
use Illuminate\Support\ServiceProvider;

/**
 * Event Service Provider
 * 
 * Registers event-related services and handlers
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
        // Register EventPublisher as singleton
        $this->app->singleton(EventPublisher::class, function ($app) {
            return new EventPublisher();
        });

        // Register EventPayloadBuilder as singleton (optional)
        $this->app->singleton(EventPayloadBuilder::class, function ($app) {
            return new EventPayloadBuilder();
        });

        // Register TournamentEventHandler as singleton
        $this->app->singleton(TournamentEventHandler::class, function ($app) {
            return new TournamentEventHandler();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register event handlers here if needed
        // This is useful if you want to register handlers dynamically
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            EventPublisher::class,
            EventPayloadBuilder::class,
            TournamentEventHandler::class,
        ];
    }
}
