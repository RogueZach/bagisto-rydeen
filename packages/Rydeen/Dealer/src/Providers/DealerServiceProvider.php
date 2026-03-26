<?php

namespace Rydeen\Dealer\Providers;

use Illuminate\Support\Facades\View;
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

        $this->app->bind(
            \Webkul\B2BSuite\DataGrids\Admin\CompanyDataGrid::class,
            \Rydeen\Dealer\DataGrids\RydeenCompanyDataGrid::class
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

        // Fix B2B Suite bug: customer create partial needs $channels and $groups
        // but not all controllers that include it pass these variables.
        View::composer('admin::customers.customers.index.create', function ($view) {
            $data = $view->getData();

            if (! isset($data['channels'])) {
                $view->with('channels', core()->getAllChannels());
            }

            if (! isset($data['groups'])) {
                $view->with('groups', app(\Webkul\Customer\Repositories\CustomerGroupRepository::class)
                    ->findWhere([['code', '<>', 'guest']]));
            }
        });
    }
}
