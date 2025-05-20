<?php

namespace App\Modules\Larastore\Providers;

use Illuminate\Support\ServiceProvider;

class LarastoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '../database/migrations');
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}