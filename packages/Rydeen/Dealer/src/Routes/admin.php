<?php

use Illuminate\Support\Facades\Route;
use Rydeen\Dealer\Http\Controllers\Admin\DealerApprovalController;
use Rydeen\Dealer\Http\Controllers\Admin\SettingsController;
use Rydeen\Dealer\Http\Controllers\Admin\DealerContactController;
use Rydeen\Dealer\Http\Controllers\Admin\ImpersonationController;
use Rydeen\Dealer\Http\Controllers\Admin\OrderApprovalController;

// Redirect bare /admin/rydeen to dealers index
Route::middleware(['web', 'admin'])->get('admin/rydeen', fn () => redirect()->route('admin.rydeen.dealers.index'));

Route::middleware(['web', 'admin'])->prefix('admin/rydeen/dealers')->group(function () {
    Route::get('/', [DealerApprovalController::class, 'index'])->name('admin.rydeen.dealers.index');
    Route::get('{id}', [DealerApprovalController::class, 'view'])->name('admin.rydeen.dealers.view');
    Route::post('{id}/approve', [DealerApprovalController::class, 'approve'])->name('admin.rydeen.dealers.approve');
    Route::post('{id}/reject', [DealerApprovalController::class, 'reject'])->name('admin.rydeen.dealers.reject');
    Route::post('{id}/assign-rep', [DealerApprovalController::class, 'assignRep'])->name('admin.rydeen.dealers.assign-rep');
    Route::post('{id}/update-forecast', [DealerApprovalController::class, 'updateForecastLevel'])->name('admin.rydeen.dealers.update-forecast');
    Route::post('{id}/resend-invitation', [DealerApprovalController::class, 'resendInvitation'])->name('admin.rydeen.dealers.resend-invitation');
    Route::post('{id}/impersonate', [ImpersonationController::class, 'start'])->name('admin.rydeen.dealers.impersonate');
});

Route::middleware(['web', 'admin'])->prefix('admin/rydeen/orders')->group(function () {
    Route::get('/', [OrderApprovalController::class, 'index'])->name('admin.rydeen.orders.index');
    Route::get('{id}', [OrderApprovalController::class, 'view'])->name('admin.rydeen.orders.view');
    Route::post('{id}/approve', [OrderApprovalController::class, 'approve'])->name('admin.rydeen.orders.approve');
    Route::post('{id}/hold', [OrderApprovalController::class, 'hold'])->name('admin.rydeen.orders.hold');
    Route::post('{id}/cancel', [OrderApprovalController::class, 'cancel'])->name('admin.rydeen.orders.cancel');
});

Route::middleware(['web', 'admin'])->prefix('admin/rydeen/contacts')->group(function () {
    Route::get('/', [DealerContactController::class, 'index'])->name('admin.rydeen.contacts.index');
    Route::get('{id}', [DealerContactController::class, 'view'])->name('admin.rydeen.contacts.view');
    Route::put('{id}', [DealerContactController::class, 'update'])->name('admin.rydeen.contacts.update');
});

Route::middleware(['web', 'admin'])->prefix('admin/rydeen/settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('admin.rydeen.settings.index');
    Route::put('/', [SettingsController::class, 'update'])->name('admin.rydeen.settings.update');
});
