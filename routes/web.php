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

Route::get('/', [AuthController::class, 'home'])->name('home');

// ---------- احراز هویت و مدیریت نشست ----------
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/session/keep-alive', [AuthController::class, 'keepAlive'])->name('session.keep-alive');

// ---------- بخش داخلی سیستم ----------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // درخواست‌های بارگیری و ویرایش واگن (شماره واگن / صاحب کالا)
    Route::get('/calculator', [CargoController::class, 'calculator'])->name('calculator.index');
    Route::get('/cargo', [CargoController::class, 'index'])->name('cargo.index');
    Route::get('/cargo/create', [CargoController::class, 'create'])->name('cargo.create');
    Route::post('/cargo', [CargoController::class, 'store'])->name('cargo.store');
    Route::get('/cargo/{cargo}', [CargoController::class, 'show'])->name('cargo.show');
    Route::get('/cargo/{cargo}/print', [CargoController::class, 'printSettlement'])->name('cargo.print');
    Route::post('/cargo/{cargo}/load', [CargoController::class, 'storeLoading'])->name('cargo.load');
    Route::post('/cargo/{cargo}/wagons', [CargoController::class, 'addWagon'])->name('cargo.wagons.store');
    Route::post('/cargo/{cargo}/quick-bill', [CargoController::class, 'quickBill'])->name('cargo.quick-bill');
    Route::get('/wagons/{wagon}/edit-owner', [CargoController::class, 'editWagonOwner'])->name('wagons.edit-owner');
    Route::get('/wagons/{wagon}/edit', [CargoController::class, 'editWagonOwner'])->name('wagons.edit');
    Route::match(['post', 'put'], '/wagons/{wagon}/owner', [CargoController::class, 'updateWagonOwner'])->name('wagons.update-owner');
    Route::match(['post', 'put'], '/wagons/{wagon}', [CargoController::class, 'updateWagonOwner'])->name('wagons.update');
    Route::delete('/wagons/{wagon}', [CargoController::class, 'destroyWagon'])->name('wagons.destroy');
    Route::post('/wagons/{wagon}/delete', [CargoController::class, 'destroyWagon'])->name('wagons.delete');

    // صورتحساب‌ها
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{billing}', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/{billing}/settle', [BillingController::class, 'settle'])->name('billing.settle');
    Route::get('/billing/{billing}/print', [BillingController::class, 'print'])->name('billing.print');
    Route::post('/billing/{billing}/cancel', [BillingController::class, 'cancel'])
        ->middleware('role:admin')
        ->name('billing.cancel');

    // گزارش‌ها (برای همه نقش‌ها؛ فقط خواندنی)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/pending-loading', [ReportController::class, 'pendingLoading'])->name('pending-loading');
        Route::get('/setad', [ReportController::class, 'setad'])->name('setad');
        Route::get('/daily-loading', [ReportController::class, 'dailyLoading'])->name('daily-loading');
        Route::get('/billings', [ReportController::class, 'billings'])->name('billings');
        Route::get('/operators', [ReportController::class, 'operators'])->name('operators');
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/wagon-detail', [ReportController::class, 'wagonDetail'])->name('wagon-detail');
        Route::get('/owners', [ReportController::class, 'owners'])->name('owners');
        Route::get('/representatives', [ReportController::class, 'representatives'])->name('representatives');
    });

    // صاحبان کالا (قابل دسترس برای اپراتور و ادمین)
    Route::get('/owners', [OwnerController::class, 'index'])->name('owners.index');
    Route::get('/owners/create', [OwnerController::class, 'create'])->name('owners.create');
    Route::post('/owners', [OwnerController::class, 'store'])->name('owners.store');
    Route::get('/owners/{owner}/edit', [OwnerController::class, 'edit'])->name('owners.edit');
    Route::put('/owners/{owner}', [OwnerController::class, 'update'])->name('owners.update');
    Route::patch('/owners/{owner}/toggle', [OwnerController::class, 'toggleActive'])->name('owners.toggle');
    Route::get('/owners/{owner}/history', [OwnerController::class, 'history'])->name('owners.history');
    Route::delete('/owners/{owner}', [OwnerController::class, 'destroy'])->name('owners.destroy');

    // نمایندگان (قابل دسترس برای اپراتور و ادمین)
    Route::get('/representatives', [RepresentativeController::class, 'index'])->name('representatives.index');
    Route::get('/representatives/create', [RepresentativeController::class, 'create'])->name('representatives.create');
    Route::post('/representatives', [RepresentativeController::class, 'store'])->name('representatives.store');
    Route::get('/representatives/{representative}/edit', [RepresentativeController::class, 'edit'])->name('representatives.edit');
    Route::put('/representatives/{representative}', [RepresentativeController::class, 'update'])->name('representatives.update');
    Route::patch('/representatives/{representative}/toggle', [RepresentativeController::class, 'toggleActive'])->name('representatives.toggle');
    Route::get('/representatives/{representative}/history', [RepresentativeController::class, 'history'])->name('representatives.history');
    Route::delete('/representatives/{representative}', [RepresentativeController::class, 'destroy'])->name('representatives.destroy');

    // ---------- مدیریت (فقط ادمین) ----------
    Route::middleware('role:admin')->group(function () {
        // تعرفه‌ها
        Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('/tariffs/create', [TariffController::class, 'create'])->name('tariffs.create');
        Route::post('/tariffs', [TariffController::class, 'store'])->name('tariffs.store');
        Route::post('/tariffs/import', [TariffController::class, 'importExcel'])->name('tariffs.import');
        Route::get('/tariffs/{tariff}/edit', [TariffController::class, 'edit'])->name('tariffs.edit');
        Route::put('/tariffs/{tariff}', [TariffController::class, 'update'])->name('tariffs.update');
        Route::patch('/tariffs/{tariff}/toggle', [TariffController::class, 'toggleActive'])->name('tariffs.toggle');
        Route::delete('/tariffs/{tariff}', [TariffController::class, 'destroy'])->name('tariffs.destroy');
        Route::post('/tariffs/{tariff}/delete', [TariffController::class, 'destroy'])->name('tariffs.delete');

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
