<?php

namespace App\Modules\Larastore\Providers;


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

        $this->loadRoutesFrom(__DIR__ . '../routes/api.php');
    }
}