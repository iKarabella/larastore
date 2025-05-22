<?php

namespace App\Modules\Larastore\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
        Route::middleware('web')
             ->namespace('App\Modules\Larastore\routes')
             ->group(__DIR__ . '/../routes/web.php');
    }
}