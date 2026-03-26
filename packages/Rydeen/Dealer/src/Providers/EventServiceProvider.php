<?php

namespace Rydeen\Dealer\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Rydeen\Dealer\Listeners\CompanyInvitationListener;
use Rydeen\Dealer\Listeners\OrderListener;
use Rydeen\Dealer\Listeners\ProductListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'customer.registration.after' => [
            [CompanyInvitationListener::class, 'afterCreated'],
        ],

        'checkout.order.save.after' => [
            [OrderListener::class, 'afterOrderCreated'],
        ],

        'catalog.product.create.after' => [
            [ProductListener::class, 'afterSave'],
        ],

        'catalog.product.update.after' => [
            [ProductListener::class, 'afterSave'],
        ],
    ];
}
