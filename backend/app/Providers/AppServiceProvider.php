<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Alias the role middleware in case Kernel routeMiddleware isn't used by the test runner.
        if ($this->app->bound('router')) {
            $this->app['router']->aliasMiddleware('role', \App\Http\Middleware\RoleMiddleware::class);
        }
    }
}
