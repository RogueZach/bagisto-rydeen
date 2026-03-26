<?php

namespace Rydeen\Auth\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'rydeen-auth');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'rydeen-auth');

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $router->aliasMiddleware('device.verify', \Rydeen\Auth\Http\Middleware\DeviceVerification::class);
        $router->aliasMiddleware('redirect.standard.auth', \Rydeen\Auth\Http\Middleware\RedirectStandardAuth::class);
        $router->pushMiddlewareToGroup('web', \Rydeen\Auth\Http\Middleware\RedirectStandardAuth::class);
    }
}
