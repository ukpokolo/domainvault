<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DomainController as AdminDomainController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\AiSearchController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/ai-search', AiSearchController::class)->middleware('throttle:20,1')->name('marketplace.ai-search');
Route::get('/domains/{domain}', [MarketplaceController::class, 'show'])->name('marketplace.show');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{domain}', [CartController::class, 'store'])->name('cart.store');
    Route::delete('/cart/{domain}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/domains', [AdminDomainController::class, 'index'])->name('domains.index');
    Route::patch('/domains/{domain}/status', [AdminDomainController::class, 'updateStatus'])->name('domains.status');
    Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
    Route::post('/review/{domain}/approve', [ReviewController::class, 'approve'])->name('review.approve');
    Route::post('/review/{domain}/reject', [ReviewController::class, 'reject'])->name('review.reject');
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/imports/{batch}/errors', [ImportController::class, 'errors'])->name('imports.errors');
    Route::get('/audit-log', AuditLogController::class)->name('audit-log');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
