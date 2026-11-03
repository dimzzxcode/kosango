<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Superadmin\BankAccountController;
use App\Http\Controllers\Superadmin\CityController as SuperadminCityController;
use App\Http\Controllers\Superadmin\FacilityController;
use App\Http\Controllers\Superadmin\PageController as SuperadminPageController;
use App\Http\Controllers\Superadmin\PropertyController as SuperadminPropertyController;
use App\Http\Controllers\Superadmin\ReviewController as SuperadminReviewController;
use App\Http\Controllers\Superadmin\RoomController as SuperadminRoomController;
use App\Http\Controllers\Superadmin\RoomTypeController;
use App\Http\Controllers\Superadmin\RoomTypePriceController;
use App\Http\Controllers\Superadmin\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('properties/{property:slug}', [PropertyController::class, 'show'])->name('properties.show');

Route::get('kota/{city:slug}', [CityController::class, 'show'])->name('cities.show');
Route::get('halaman/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('ketersediaan', AvailabilityController::class)
    ->middleware('throttle:30,1')
    ->name('availability');

Route::get('sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::post('webhooks/payment', [WebhookController::class, 'payment'])->name('webhooks.payment');

/*
|--------------------------------------------------------------------------
| Customer routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings', [BookingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('bookings.store');
    Route::get('bookings/{booking:code}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('bookings/{booking:code}/refund-preview', [BookingController::class, 'refundPreview'])
        ->name('bookings.refund-preview');
    Route::post('bookings/{booking:code}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');
    Route::post('bookings/{booking:code}/extend', [BookingController::class, 'extend'])
        ->name('bookings.extend');

    Route::post('bookings/{booking:code}/invoices/{invoice}/payments', [PaymentController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('payments.store');
    Route::get('payments/{payment}/proof', [PaymentController::class, 'proof'])->name('payments.proof');

    Route::post('refunds/{refund}/account', [RefundController::class, 'submitAccount'])->name('refunds.account');
    Route::get('refunds/{refund}/proof', [RefundController::class, 'proof'])->name('refunds.proof');

    Route::post('bookings/{booking:code}/review', [ReviewController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('reviews.store');
});

/*
|--------------------------------------------------------------------------
| Staff routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:superadmin,admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking:code}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking:code}/complete', [AdminBookingController::class, 'complete'])
            ->name('bookings.complete');
        Route::post('bookings/{booking:code}/cancel', [AdminBookingController::class, 'cancel'])
            ->name('bookings.cancel');

        Route::post('payments/{payment}/verify', [AdminPaymentController::class, 'verify'])
            ->name('payments.verify');

        Route::get('invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices/{invoice}/reopen-upload', [AdminInvoiceController::class, 'reopenUpload'])
            ->name('invoices.reopen-upload');

        Route::get('refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
        Route::post('refunds/{refund}/process', [AdminRefundController::class, 'process'])
            ->name('refunds.process');
        Route::post('refunds/{refund}/reject', [AdminRefundController::class, 'reject'])
            ->name('refunds.reject');

        Route::get('properties/{property:slug}/rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
        Route::patch('rooms/{room}/status', [AdminRoomController::class, 'updateStatus'])->name('rooms.status');
    });

/*
|--------------------------------------------------------------------------
| Superadmin routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function (): void {
        Route::get('cities', [SuperadminCityController::class, 'index'])->name('cities.index');
        Route::post('cities', [SuperadminCityController::class, 'store'])->name('cities.store');
        Route::put('cities/{city:slug}', [SuperadminCityController::class, 'update'])->name('cities.update');
        Route::delete('cities/{city:slug}', [SuperadminCityController::class, 'destroy'])->name('cities.destroy');

        Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index');
        Route::post('facilities', [FacilityController::class, 'store'])->name('facilities.store');
        Route::put('facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
        Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');

        Route::get('bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::post('bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::put('bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::put('users/{user}/properties', [UserController::class, 'assign'])->name('users.assign');

        Route::get('pages', [SuperadminPageController::class, 'index'])->name('pages.index');
        Route::post('pages', [SuperadminPageController::class, 'store'])->name('pages.store');
        Route::put('pages/{page}', [SuperadminPageController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [SuperadminPageController::class, 'destroy'])->name('pages.destroy');

        Route::get('reviews', [SuperadminReviewController::class, 'index'])->name('reviews.index');
        Route::patch('reviews/{review}/visibility', [SuperadminReviewController::class, 'visibility'])
            ->name('reviews.visibility');
        Route::delete('reviews/{review}', [SuperadminReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::get('properties', [SuperadminPropertyController::class, 'index'])->name('properties.index');
        Route::get('properties/create', [SuperadminPropertyController::class, 'create'])->name('properties.create');
        Route::post('properties', [SuperadminPropertyController::class, 'store'])->name('properties.store');
        Route::get('properties/{property}/edit', [SuperadminPropertyController::class, 'edit'])->name('properties.edit');
        Route::put('properties/{property}', [SuperadminPropertyController::class, 'update'])->name('properties.update');
        Route::delete('properties/{property}', [SuperadminPropertyController::class, 'destroy'])->name('properties.destroy');
        Route::patch('properties/{property}/publish', [SuperadminPropertyController::class, 'publish'])
            ->name('properties.publish');
        Route::delete('properties/{property}/images/{image}', [SuperadminPropertyController::class, 'destroyImage'])
            ->name('properties.images.destroy');
        Route::put('properties/{property}/images/order', [SuperadminPropertyController::class, 'reorderImages'])
            ->name('properties.images.order');
        Route::put('properties/{property}/admins', [SuperadminPropertyController::class, 'admins'])
            ->name('properties.admins');

        Route::post('properties/{property}/room-types', [RoomTypeController::class, 'store'])
            ->name('room-types.store');
        Route::put('room-types/{roomType}', [RoomTypeController::class, 'update'])->name('room-types.update');
        Route::delete('room-types/{roomType}', [RoomTypeController::class, 'destroy'])->name('room-types.destroy');
        Route::put('room-types/{roomType}/prices', [RoomTypePriceController::class, 'update'])
            ->name('room-types.prices.update');

        Route::post('rooms', [SuperadminRoomController::class, 'store'])->name('rooms.store');
        Route::put('rooms/{room}', [SuperadminRoomController::class, 'update'])->name('rooms.update');
        Route::delete('rooms/{room}', [SuperadminRoomController::class, 'destroy'])->name('rooms.destroy');
    });

require __DIR__.'/settings.php';
