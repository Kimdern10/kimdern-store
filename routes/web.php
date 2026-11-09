<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SitemapController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
| Storefront
*/
Route::livewire('/', 'pages::shop.home')->name('home');
Route::livewire('shop', 'pages::shop.catalog')->name('shop');
Route::livewire('category/{category:slug}', 'pages::shop.catalog')->name('category');
Route::livewire('product/{product:slug}', 'pages::shop.product')->name('product');
Route::livewire('cart', 'pages::shop.cart')->name('cart');
Route::livewire('checkout', 'pages::shop.checkout')->name('checkout');

Route::get('checkout/callback', [PaymentController::class, 'callback'])->name('checkout.callback');
Route::post('paystack/webhook', [PaymentController::class, 'webhook'])->name('paystack.webhook');

Route::get('track-order', [OrderController::class, 'track'])->name('orders.track');
Route::middleware('signed')->group(function () {
    Route::get('orders/{order:number}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order:number}/receipt', [OrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('orders/{order:number}/pay', [OrderController::class, 'pay'])->name('orders.pay');
});

Route::view('contact', 'shop.page', ['eyebrow' => 'We are here to help', 'heading' => 'Contact us', 'intro' => 'Questions about a product, an order or a delivery? Reach us any way you like.', 'content' => 'shop.pages.contact'])->name('contact');
Route::view('delivery-and-returns', 'shop.page', ['eyebrow' => 'Good to know', 'heading' => 'Delivery & returns', 'content' => 'shop.pages.shipping'])->name('shipping');
Route::view('privacy-policy', 'shop.page', ['eyebrow' => 'Your data', 'heading' => 'Privacy & cookies', 'content' => 'shop.pages.privacy'])->name('privacy');

Route::get('sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
| Customer account
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn (): RedirectResponse => Auth::user()->is_admin
        ? redirect()->route('admin.dashboard')
        : redirect()->route('account.orders'))->name('dashboard');

    Route::livewire('account', 'pages::account.orders')->name('account.orders');
    Route::livewire('account/orders/{order:number}', 'pages::account.order')->name('account.order');
    Route::livewire('account/addresses', 'pages::account.addresses')->name('account.addresses');
    Route::livewire('account/wishlist', 'pages::account.wishlist')->name('account.wishlist');
});

/*
| Shop admin
*/
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('orders', 'pages::admin.orders')->name('orders');
    Route::livewire('orders/{order:number}', 'pages::admin.order')->name('orders.show');
    Route::livewire('products', 'pages::admin.products')->name('products');
    Route::livewire('products/create', 'pages::admin.product-form')->name('products.create');
    Route::livewire('products/{product:id}/edit', 'pages::admin.product-form')->name('products.edit');
    Route::livewire('categories', 'pages::admin.categories')->name('categories');
    Route::livewire('coupons', 'pages::admin.coupons')->name('coupons');
    Route::livewire('delivery-zones', 'pages::admin.zones')->name('zones');
    Route::livewire('customers', 'pages::admin.customers')->name('customers');
    Route::livewire('reviews', 'pages::admin.reviews')->name('reviews');
    Route::livewire('banners', 'pages::admin.banners')->name('banners');
    Route::livewire('settings', 'pages::admin.settings')->name('settings');
});

require __DIR__.'/settings.php';
