<?php

use Illuminate\Support\Facades\Route;
use Rydeen\Dealer\Http\Controllers\Admin\DealerApprovalController;
use Rydeen\Dealer\Http\Controllers\Admin\OrderApprovalController;

Route::middleware(['web', 'admin'])->prefix('admin/rydeen/dealers')->group(function () {
    Route::get('/', [DealerApprovalController::class, 'index'])->name('admin.rydeen.dealers.index');
    Route::get('{id}', [DealerApprovalController::class, 'view'])->name('admin.rydeen.dealers.view');
    Route::post('{id}/approve', [DealerApprovalController::class, 'approve'])->name('admin.rydeen.dealers.approve');
    Route::post('{id}/reject', [DealerApprovalController::class, 'reject'])->name('admin.rydeen.dealers.reject');
    Route::post('{id}/assign-rep', [DealerApprovalController::class, 'assignRep'])->name('admin.rydeen.dealers.assign-rep');
    Route::post('{id}/update-forecast', [DealerApprovalController::class, 'updateForecastLevel'])->name('admin.rydeen.dealers.update-forecast');
});

Route::middleware(['web', 'admin'])->prefix('admin/rydeen/orders')->group(function () {
    Route::get('/', [OrderApprovalController::class, 'index'])->name('admin.rydeen.orders.index');
    Route::get('{id}', [OrderApprovalController::class, 'view'])->name('admin.rydeen.orders.view');
    Route::post('{id}/approve', [OrderApprovalController::class, 'approve'])->name('admin.rydeen.orders.approve');
    Route::post('{id}/hold', [OrderApprovalController::class, 'hold'])->name('admin.rydeen.orders.hold');
    Route::post('{id}/cancel', [OrderApprovalController::class, 'cancel'])->name('admin.rydeen.orders.cancel');
});
