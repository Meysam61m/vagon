<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RepresentativeController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// ---------- احراز هویت ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ---------- بخش داخلی سیستم ----------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // درخواست‌های بارگیری و ویرایش واگن (شماره واگن / صاحب کالا)
    Route::get('/cargo', [CargoController::class, 'index'])->name('cargo.index');
    Route::get('/cargo/create', [CargoController::class, 'create'])->name('cargo.create');
    Route::post('/cargo', [CargoController::class, 'store'])->name('cargo.store');
    Route::get('/cargo/{cargo}', [CargoController::class, 'show'])->name('cargo.show');
    Route::post('/cargo/{cargo}/load', [CargoController::class, 'storeLoading'])->name('cargo.load');
    Route::get('/wagons/{wagon}/edit-owner', [CargoController::class, 'editWagonOwner'])->name('wagons.edit-owner');
    Route::get('/wagons/{wagon}/edit', [CargoController::class, 'editWagonOwner'])->name('wagons.edit');
    Route::match(['post', 'put'], '/wagons/{wagon}/owner', [CargoController::class, 'updateWagonOwner'])->name('wagons.update-owner');
    Route::match(['post', 'put'], '/wagons/{wagon}', [CargoController::class, 'updateWagonOwner'])->name('wagons.update');

    // صورتحساب‌ها
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{billing}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('/billing/{billing}/print', [BillingController::class, 'print'])->name('billing.print');
    Route::post('/billing/{billing}/cancel', [BillingController::class, 'cancel'])
        ->middleware('role:admin')
        ->name('billing.cancel');

    // گزارش‌ها (برای همه نقش‌ها؛ فقط خواندنی)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/daily-loading', [ReportController::class, 'dailyLoading'])->name('daily-loading');
        Route::get('/billings', [ReportController::class, 'billings'])->name('billings');
        Route::get('/operators', [ReportController::class, 'operators'])->name('operators');
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/wagon-detail', [ReportController::class, 'wagonDetail'])->name('wagon-detail');
        Route::get('/owners', [ReportController::class, 'owners'])->name('owners');
        Route::get('/representatives', [ReportController::class, 'representatives'])->name('representatives');
    });

    // ---------- مدیریت (فقط ادمین) ----------
    Route::middleware('role:admin')->group(function () {
        // صاحبان کالا
        Route::get('/owners', [OwnerController::class, 'index'])->name('owners.index');
        Route::get('/owners/create', [OwnerController::class, 'create'])->name('owners.create');
        Route::post('/owners', [OwnerController::class, 'store'])->name('owners.store');
        Route::get('/owners/{owner}/edit', [OwnerController::class, 'edit'])->name('owners.edit');
        Route::put('/owners/{owner}', [OwnerController::class, 'update'])->name('owners.update');
        Route::patch('/owners/{owner}/toggle', [OwnerController::class, 'toggleActive'])->name('owners.toggle');
        Route::get('/owners/{owner}/history', [OwnerController::class, 'history'])->name('owners.history');
        Route::delete('/owners/{owner}', [OwnerController::class, 'destroy'])->name('owners.destroy');

        // نمایندگان
        Route::get('/representatives', [RepresentativeController::class, 'index'])->name('representatives.index');
        Route::get('/representatives/create', [RepresentativeController::class, 'create'])->name('representatives.create');
        Route::post('/representatives', [RepresentativeController::class, 'store'])->name('representatives.store');
        Route::get('/representatives/{representative}/edit', [RepresentativeController::class, 'edit'])->name('representatives.edit');
        Route::put('/representatives/{representative}', [RepresentativeController::class, 'update'])->name('representatives.update');
        Route::patch('/representatives/{representative}/toggle', [RepresentativeController::class, 'toggleActive'])->name('representatives.toggle');
        Route::get('/representatives/{representative}/history', [RepresentativeController::class, 'history'])->name('representatives.history');
        Route::delete('/representatives/{representative}', [RepresentativeController::class, 'destroy'])->name('representatives.destroy');

        // تعرفه‌ها
        Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('/tariffs/create', [TariffController::class, 'create'])->name('tariffs.create');
        Route::post('/tariffs', [TariffController::class, 'store'])->name('tariffs.store');
        Route::get('/tariffs/{tariff}/edit', [TariffController::class, 'edit'])->name('tariffs.edit');
        Route::put('/tariffs/{tariff}', [TariffController::class, 'update'])->name('tariffs.update');
        Route::patch('/tariffs/{tariff}/toggle', [TariffController::class, 'toggleActive'])->name('tariffs.toggle');

        // مالیات
        Route::get('/tax', [TaxController::class, 'edit'])->name('tax.edit');
        Route::put('/tax', [TaxController::class, 'update'])->name('tax.update');

        // کاربران
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
