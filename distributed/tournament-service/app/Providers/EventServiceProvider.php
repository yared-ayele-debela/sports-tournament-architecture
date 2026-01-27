<?php

namespace App\Providers;

use App\Observers\TournamentObserver;
use App\Models\Tournament;
use App\Services\Events\EventPublisher;
use Illuminate\Support\ServiceProvider;

/**
 * Event Service Provider
 * 
 * Registers event-related services and model observers
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
        $this->app->singleton(\App\Services\Events\EventPayloadBuilder::class, function ($app) {
            return new \App\Services\Events\EventPayloadBuilder();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register model observers
        Tournament::observe(TournamentObserver::class);

        // You can add more observers here as needed:
        // Sport::observe(SportObserver::class);
        // Venue::observe(VenueObserver::class);
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
            \App\Services\Events\EventPayloadBuilder::class,
        ];
    }
}
