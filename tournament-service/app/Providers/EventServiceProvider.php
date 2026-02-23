<?php

namespace App\Providers;

use App\Observers\TournamentObserver;
use App\Models\Tournament;
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
        // Event-related services registration removed
        // QueuePublisher is registered in AppServiceProvider
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
        return [];
    }
}
