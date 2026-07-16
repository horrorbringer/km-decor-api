<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AdminBrandController;
use App\Http\Controllers\Api\AdminCategoryController;
use App\Http\Controllers\Api\AdminInquiryController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\AdminProductController;
use App\Http\Controllers\Api\AdminServiceController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CustomerInquiryController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReorderController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\SeoController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::patch('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist', [WishlistController::class, 'clear'])->name('wishlist.clear');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/reorder', ReorderController::class)->middleware('throttle:10,1')->name('orders.reorder');
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->middleware('throttle:5,1')->name('payments.store');
    Route::get('/inquiries', [CustomerInquiryController::class, 'index'])->name('customer.inquiries.index');
    Route::get('/inquiries/{inquiry}', [CustomerInquiryController::class, 'show'])->name('customer.inquiries.show');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/orders/{order}/invoice', [InvoiceController::class, 'generateFromOrder'])->name('orders.invoice');

    Route::post('/auth/link', [SocialAuthController::class, 'link'])->name('social.link');
    Route::post('/auth/unlink', [SocialAuthController::class, 'unlink'])->name('social.unlink');

    Route::middleware('staff:super_admin,admin,order_manager')->prefix('admin')->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('admin.orders.update');
    });

    Route::middleware('staff:super_admin,admin,sales_staff')->prefix('admin')->group(function () {
        Route::get('/inquiries', [AdminInquiryController::class, 'index'])->name('admin.inquiries.index');
        Route::get('/inquiries/{inquiry}', [AdminInquiryController::class, 'show'])->name('admin.inquiries.show');
        Route::patch('/inquiries/{inquiry}', [AdminInquiryController::class, 'update'])->name('admin.inquiries.update');
    });

    Route::middleware('staff:super_admin,admin')->prefix('admin')->group(function () {
        Route::post('/payments/{payment}/confirm', [AdminPaymentController::class, 'confirm'])->name('admin.payments.confirm');
        Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('admin.payments.reject');
        Route::apiResource('categories', AdminCategoryController::class)->names('admin.categories');
        Route::apiResource('brands', AdminBrandController::class)->names('admin.brands');
        Route::apiResource('products', AdminProductController::class)->names('admin.products');
        Route::post('/products/{product}/images', [AdminProductController::class, 'storeImage'])->name('admin.products.images.store');
        Route::patch('/products/{product}/images/{image}', [AdminProductController::class, 'updateImage'])->name('admin.products.images.update');
        Route::delete('/products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->name('admin.products.images.destroy');
        Route::apiResource('services', AdminServiceController::class)->names('admin.services');
        Route::apiResource('users', AdminUserController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->names('admin.users');
    });
});

Route::get('/email/verify/{user}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed:relative', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/checkout', CheckoutController::class)->middleware('throttle:10,1')->name('checkout');

Route::get('/health', HealthController::class)->name('health');
Route::get('/home', HomeController::class)->name('home');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/portfolio', [ProjectController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{project:slug}', [ProjectController::class, 'show'])->name('portfolio.show');
Route::get('/search', SearchController::class)->middleware('throttle:60,1')->name('search');
Route::post('/contact', ContactController::class)->middleware('throttle:10,1');
Route::post('/inquiries', InquiryController::class)->middleware('throttle:10,1');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
