<?php

use Illuminate\Support\Facades\Route;
use Webfox\Xero\Controllers\AuthorizationCallbackController;
use Webfox\Xero\Controllers\AuthorizationController;

Route::middleware('web')->group(function () {
    Route::get('/xero/auth/authorize', AuthorizationController::class)->name('xero.auth.authorize');
    Route::get('/xero/auth/callback', AuthorizationCallbackController::class)->name('xero.auth.callback');
});
