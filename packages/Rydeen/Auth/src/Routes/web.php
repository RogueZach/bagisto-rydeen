<?php

use Illuminate\Support\Facades\Route;
use Rydeen\Auth\Http\Controllers\LoginController;

Route::middleware('web')->prefix('dealer')->group(function () {
    // Redirect bare /dealer to login
    Route::get('/', fn () => redirect()->route('dealer.login'));

    Route::get('login', [LoginController::class, 'showLogin'])->name('dealer.login');
    Route::post('login', [LoginController::class, 'login'])->name('dealer.login.submit');
    Route::post('login/send-code', [LoginController::class, 'sendCode'])->name('dealer.login.send-code');
    Route::get('register', [LoginController::class, 'showRegister'])->name('dealer.register');
    Route::post('register', [LoginController::class, 'register'])->name('dealer.register.submit')->middleware('throttle:5,1');
    Route::get('verify', [LoginController::class, 'showVerify'])->name('dealer.verify.form');
    Route::post('verify', [LoginController::class, 'verify'])->name('dealer.verify');
    Route::post('resend-code', [LoginController::class, 'resendCode'])->name('dealer.resend-code');
    Route::post('logout', [LoginController::class, 'logout'])->name('dealer.logout');
});
