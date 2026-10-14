<?php

use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:api-login')->name('api.tokens.store');

Route::middleware(['auth:sanctum', 'throttle:api'])->name('api.')->group(function () {
    Route::delete('/tokens', [TokenController::class, 'destroy'])->name('tokens.destroy');

    Route::middleware('abilities:domains:read')->group(function () {
        Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
        Route::get('/domains/{domain}', [DomainController::class, 'show'])->name('domains.show');
    });
});
