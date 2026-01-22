<?php

namespace App\Providers;

use App\Services\EventSubscriber;
use App\Listeners\CacheInvalidationListener;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;

class CacheInvalidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the EventSubscriber as a singleton
        $this->app->singleton(EventSubscriber::class, function ($app) {
            return new EventSubscriber(
                $app->make(\App\Services\Aggregators\TournamentAggregator::class),
                $app->make(\App\Services\Aggregators\MatchAggregator::class),
                $app->make(\App\Services\Aggregators\TeamAggregator::class)
            );
        });

        // Register the CacheInvalidationListener
        $this->app->singleton(CacheInvalidationListener::class, function ($app) {
            return new CacheInvalidationListener();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure queue for cache invalidation events
        Queue::after(function ($event) {
            // Log any cache invalidation events that were processed via queue
            if (str_contains($event->job->resolveName(), 'CacheInvalidation')) {
                \Illuminate\Support\Facades\Log::info('Cache invalidation job processed', [
                    'job' => $event->job->resolveName(),
                    'connection' => $event->connectionName,
                    'queue' => $event->job->getQueue(),
                ]);
            }
        });
    }
}
