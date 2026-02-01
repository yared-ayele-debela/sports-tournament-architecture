<?php

namespace App\Providers;

use App\Services\Queue\QueuePublisher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register QueuePublisher as singleton
        $this->app->singleton(QueuePublisher::class, function ($app) {
            return new QueuePublisher();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
