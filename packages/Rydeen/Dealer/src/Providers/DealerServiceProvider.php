<?php

namespace Rydeen\Dealer\Providers;

use Illuminate\Support\ServiceProvider;

class DealerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/paymentmethods.php', 'payment_methods');
        $this->mergeConfigFrom(__DIR__ . '/../Config/carriers.php', 'carriers');

        // Fix B2B Suite bug: CustomerController::index() missing $channels
        $this->app->bind(
            \Webkul\Admin\Http\Controllers\Customers\CustomerController::class,
            \Rydeen\Dealer\Http\Controllers\Admin\CustomerController::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'rydeen-dealer');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'rydeen-dealer');

        $this->loadRoutesFrom(__DIR__ . '/../Routes/shop.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
    }
}
