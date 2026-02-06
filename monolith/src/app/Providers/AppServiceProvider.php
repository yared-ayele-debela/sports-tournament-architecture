<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use App\Services\StandingsCalculator;
use App\Services\MatchScheduler;
use App\Services\TeamService;
use App\Services\MatchService;
use App\Services\UserService;
use App\Services\DashboardService;
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

        $this->app->singleton(TeamService::class, function ($app) {
            return new TeamService();
        });

        $this->app->singleton(MatchService::class, function ($app) {
            return new MatchService();
        });

        $this->app->singleton(UserService::class, function ($app) {
            return new UserService();
        });

        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService();
        });
    }

    public function boot(): void
    {
        // Register observers
        MatchModel::observe(MatchObserver::class);

        // Configure custom rate limiters
        $this->configureRateLimiters();
    }

    /**
     * Configure custom rate limiters for different routes
     */
    protected function configureRateLimiters(): void
    {
        // Authentication rate limiters - strict for security
        RateLimiter::for('login', function ($request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('register', function ($request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('password-reset', function ($request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(10)->by($request->ip()),
            ];
        });

        RateLimiter::for('password-confirm', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // API rate limiters - more lenient for public data
        RateLimiter::for('api', function ($request) {
            $identifier = $request->user()?->id ?: $request->ip();
            return [
                Limit::perMinute(60)->by($identifier),
                Limit::perHour(1000)->by($identifier),
            ];
        });

        // Admin operations - moderate limits
        RateLimiter::for('admin', function ($request) {
            $identifier = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(30)->by($identifier);
        });

        // Sensitive operations - strict limits
        RateLimiter::for('sensitive', function ($request) {
            $identifier = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(10)->by($identifier);
        });
    }
}
