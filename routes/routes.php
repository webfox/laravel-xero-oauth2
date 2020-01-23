<?php

use \Illuminate\Support\Facades\Route;
use \Webfox\Xero\Controllers\AuthorizationController;
use \Webfox\Xero\Controllers\AuthorizationCallbackController;

Route::middleware('web')->group(function() {
    Route::get('/xero/auth/authorize', AuthorizationController::class)->name('xero.auth.authorize');
    Route::get('/xero/auth/callback', AuthorizationCallbackController::class)->name('xero.auth.callback');
});
