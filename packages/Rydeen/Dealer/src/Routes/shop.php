<?php

use Illuminate\Support\Facades\Route;
use Rydeen\Dealer\Http\Controllers\Admin\ExportController;
use Rydeen\Dealer\Http\Controllers\Shop\CatalogController;
use Rydeen\Dealer\Http\Controllers\Shop\DashboardController;
use Rydeen\Dealer\Http\Controllers\Shop\OrderController;
use Rydeen\Dealer\Http\Controllers\Shop\ResourcesController;

Route::middleware(['web', 'customer'])->prefix('dealer')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dealer.dashboard');

    // Catalog
    Route::get('catalog', [CatalogController::class, 'index'])->name('dealer.catalog');
    Route::get('catalog/{slug}', [CatalogController::class, 'show'])->name('dealer.catalog.product');

    // Orders
    Route::get('orders', [OrderController::class, 'index'])->name('dealer.orders');
    Route::get('orders/export/csv', [ExportController::class, 'csv'])->name('dealer.orders.export.csv');
    Route::get('orders/export/pdf', [ExportController::class, 'pdf'])->name('dealer.orders.export.pdf');
    Route::get('orders/{id}', [OrderController::class, 'view'])->name('dealer.orders.view');
    Route::get('orders/{id}/print', [OrderController::class, 'print'])->name('dealer.orders.print');
    Route::post('orders/{id}/reorder', [OrderController::class, 'reorder'])->name('dealer.orders.reorder');

    // Order Review & Place
    Route::get('order-review', [OrderController::class, 'review'])->name('dealer.order-review');
    Route::post('order-review/update-item', [OrderController::class, 'updateItem'])->name('dealer.order-review.update-item');
    Route::post('order-review/remove-item', [OrderController::class, 'removeItem'])->name('dealer.order-review.remove-item');
    Route::post('order-review/place', [OrderController::class, 'placeOrder'])->name('dealer.order-review.place');

    // Cart (redirect to order review)
    Route::get('cart', function () {
        return redirect()->route('dealer.order-review');
    })->name('dealer.cart');

    // Order Confirmation
    Route::get('order-confirmation/{id}', [OrderController::class, 'confirmation'])->name('dealer.order-confirmation');

    // Resources / FAQ
    Route::get('resources', [ResourcesController::class, 'index'])->name('dealer.resources');
});
