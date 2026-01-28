<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\StandingsCalculator;
use App\Services\MatchScheduler;
use App\Observers\MatchObserver;
use App\Models\MatchModel;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(StandingsCalculator::class, function ($app) {
            return new StandingsCalculator();
        });

        $this->app->singleton(MatchScheduler::class, function ($app) {
            return new MatchScheduler();
        });
    }

    public function boot(): void
    {
        // Register observers
        MatchModel::observe(MatchObserver::class);
    }
}
