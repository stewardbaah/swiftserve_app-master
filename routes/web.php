<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\RestaurantDirectoryController;
use App\Http\Controllers\RestaurantProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SavedAddressController;
use App\Http\Controllers\Teams\TeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CityController::class, 'index'])->name('home');
Route::post('/cities/select', [CityController::class, 'select'])->name('cities.select');
Route::post('/cities/detect', [CityController::class, 'detect'])->name('cities.detect');

Route::get('/restaurants', [RestaurantDirectoryController::class, 'index'])->name('restaurants.directory');
Route::get('/restaurants/{restaurant:slug}', [RestaurantProfileController::class, 'show'])->name('restaurants.show');

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/promo', [CheckoutController::class, 'applyPromoCode'])->name('checkout.promo');
Route::post('/checkout/promo/remove', [CheckoutController::class, 'removePromoCode'])->name('checkout.promo.remove');
Route::get('/checkout/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');
Route::post('/webhooks/paystack', [CheckoutController::class, 'webhook'])->name('webhooks.paystack');

Route::get('/orders/{order}/track', [OrderTrackingController::class, 'track'])->name('orders.track');
Route::post('/orders/{order}/review', [ReviewController::class, 'store'])->name('orders.review');
Route::post('/orders/{order}/complaint', [ComplaintController::class, 'store'])->name('orders.complaint');

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::get('dashboard/payments', [DashboardController::class, 'payments'])->name('dashboard.payments');
    Route::get('dashboard/addresses', [DashboardController::class, 'addresses'])->name('dashboard.addresses');
    Route::get('dashboard/favorites', [DashboardController::class, 'favorites'])->name('dashboard.favorites');
    Route::post('/restaurants/{restaurant}/favorite', [FavoriteController::class, 'toggle'])->name('restaurants.favorite');
    Route::post('/addresses', [SavedAddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{saved_address}', [SavedAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{saved_address}', [SavedAddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/orders/{order}/reorder', [DashboardController::class, 'reorder'])->name('orders.reorder');
});

require __DIR__.'/settings.php';
