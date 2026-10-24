<?php

namespace App\Providers;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TariffController;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Tariff;
use App\Policies\OwnerPolicy;
use App\Policies\RepresentativePolicy;
use Database\Seeders\TariffSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Morilog\Jalali\Jalalian;

class AppServiceProvider extends ServiceProvider
{
    private static bool $schemaVerified = false;

    public function register(): void
    {
        // اعمال اجباری خروج با بستن مرورگر و زمان خروج خودکار ۳۰ دقیقه عدم فعالیت
        // (حتی اگر فایل .env قبلی روی سرور مقادیر قدیمی مثل 480 را داشته باشد)
        config([
            'session.lifetime' => 30,
            'session.expire_on_close' => true,
        ]);

        $cookieName = (string) config('session.cookie', '');
        if ($cookieName === '' || $cookieName === '_session' || $cookieName === 'laravel_session') {
            config(['session.cookie' => 'wagon_system_session']);
        }

        $sessionsDir = storage_path('framework/sessions');
        if (! is_dir($sessionsDir)) {
            @mkdir($sessionsDir, 0775, true);
        }
    }

    public function boot(): void
    {
        // پشت پروکسی/CDN: تشخیص درست HTTPS و IP کاربر
        $trustedProxies = trim((string) config('app.trusted_proxies', ''));

        if ($trustedProxies !== '') {
            $proxies = $trustedProxies === '*'
                ? ['0.0.0.0/0', '::/0']
                : array_map('trim', explode(',', $trustedProxies));

            Request::setTrustedProxies($proxies, Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO);
        }

        // صفحه‌بندی باید با قالب Bootstrap 5 رندر شود، نه Tailwind
        Paginator::useBootstrapFive();

        // @jdate($model->created_at) → ۱۴۰۴/۰۷/۲۵
        Blade::directive('jdate', function (string $expression) {
            return "<?php echo e(\App\Support\JalaliDate::fromCarbon($expression) ?? '-'); ?>";
        });

        // @jdatetime($date) → ۱۴۰۴/۰۷/۲۵ ۱۴:۳۰
        Blade::directive('jdatetime', function (string $expression) {
            return "<?php echo e($expression ? \\Morilog\\Jalali\\Jalalian::fromCarbon(\\Illuminate\\Support\\Carbon::instance($expression))->format('Y/m/d H:i') : '-'); ?>";
        });

        // ثبت Policyهای مدل‌های دامنه (با حفظ کامل دسترسی اپراتور به تعریف و مدیریت صاحب کالا و نماینده)
        Gate::policy(Owner::class, OwnerPolicy::class);
        Gate::policy(Representative::class, RepresentativePolicy::class);

        // اطمینان از اینکه نمونه‌سازی کلاس تاریخ شمسی در زمان اجرا با autoload کامپوزر انجام می‌شود
        class_exists(Jalalian::class);

        $this->ensureWagonOwnerRoutesRegistered();
        $this->ensureWagonOwnerSchema();
    }

    /**
     * اگر کش مسیرها (route:cache) از نسخه قبلی باقی مانده باشد یا فایل routes/web.php
     * هنوز ری‌لود نشده باشد، مسیرهای ویرایش واگن را ثبت و جدول نام مسیرها را تازه می‌کند.
     */
    private function ensureWagonOwnerRoutesRegistered(): void
    {
        $routeCache = base_path('bootstrap/cache/routes-v7.php');
        if (is_file($routeCache)) {
            $content = @file_get_contents($routeCache);
            if (
                $content === false
                || ! str_contains($content, 'wagons.edit-owner')
                || ! str_contains($content, 'wagons.destroy')
                || ! str_contains($content, 'reports.setad')
                || ! str_contains($content, 'reports.pending-loading')
                || ! str_contains($content, 'tariffs.destroy')
                || ! str_contains($content, 'cargo.quick-bill')
                || ! str_contains($content, 'cargo.print')
                || ! str_contains($content, 'session.keep-alive')
                || ! str_contains($content, 'AuthController')
            ) {
                @unlink($routeCache);
                clearstatcache(true, $routeCache);
            }
        }

        $this->app->booted(function () {
            $registered = false;

            $routeCollection = app('router')->getRoutes();
            foreach ($routeCollection->getRoutes() as $routeObj) {
                $name = (string) $routeObj->getName();
                if (str_starts_with($name, 'owners.') || str_starts_with($name, 'representatives.')) {
                    $action = $routeObj->getAction();
                    if (! empty($action['middleware']) && is_array($action['middleware'])) {
                        $filtered = array_values(array_filter(
                            $action['middleware'],
                            fn ($m) => $m !== 'role:admin'
                        ));
                        if (count($filtered) !== count($action['middleware'])) {
                            $action['middleware'] = $filtered;
                            $routeObj->setAction($action);
                        }
                    }
                }
            }

            if (! Route::has('wagons.edit-owner') || ! Route::has('wagons.edit') || ! Route::has('wagons.destroy')) {
                Route::middleware(['web', 'auth'])->group(function () {
                    Route::get('/wagons/{wagon}/edit-owner', [CargoController::class, 'editWagonOwner'])
                        ->name('wagons.edit-owner');
                    Route::get('/wagons/{wagon}/edit', [CargoController::class, 'editWagonOwner'])
                        ->name('wagons.edit');
                    Route::match(['post', 'put'], '/wagons/{wagon}/owner', [CargoController::class, 'updateWagonOwner'])
                        ->name('wagons.update-owner');
                    Route::match(['post', 'put'], '/wagons/{wagon}', [CargoController::class, 'updateWagonOwner'])
                        ->name('wagons.update');
                    Route::delete('/wagons/{wagon}', [CargoController::class, 'destroyWagon'])
                        ->name('wagons.destroy');
                    Route::post('/wagons/{wagon}/delete', [CargoController::class, 'destroyWagon'])
                        ->name('wagons.delete');
                });
                $registered = true;
            }

            if (! Route::has('cargo.quick-bill') || ! Route::has('cargo.wagons.store') || ! Route::has('calculator.index') || ! Route::has('billing.settle') || ! Route::has('cargo.print')) {
                Route::middleware(['web', 'auth'])->group(function () {
                    Route::get('/calculator', [CargoController::class, 'calculator'])
                        ->name('calculator.index');
                    Route::get('/cargo/{cargo}/print', [CargoController::class, 'printSettlement'])
                        ->name('cargo.print');
                    Route::post('/cargo/{cargo}/wagons', [CargoController::class, 'addWagon'])
                        ->name('cargo.wagons.store');
                    Route::post('/cargo/{cargo}/quick-bill', [CargoController::class, 'quickBill'])
                        ->name('cargo.quick-bill');
                    Route::post('/billing/{billing}/settle', [BillingController::class, 'settle'])
                        ->name('billing.settle');
                });
                $registered = true;
            }

            if (! Route::has('reports.setad') || ! Route::has('reports.pending-loading')) {
                Route::middleware(['web', 'auth'])->group(function () {
                    Route::get('/reports/pending-loading', [ReportController::class, 'pendingLoading'])
                        ->name('reports.pending-loading');
                    Route::get('/reports/setad', [ReportController::class, 'setad'])
                        ->name('reports.setad');
                });
                $registered = true;
            }

            if (! Route::has('tariffs.import') || ! Route::has('tariffs.destroy')) {
                Route::middleware(['web', 'auth', 'role:admin'])->group(function () {
                    Route::post('/tariffs/import', [TariffController::class, 'importExcel'])
                        ->name('tariffs.import');
                    Route::delete('/tariffs/{tariff}', [TariffController::class, 'destroy'])
                        ->name('tariffs.destroy');
                    Route::post('/tariffs/{tariff}/delete', [TariffController::class, 'destroy'])
                        ->name('tariffs.delete');
                });
                $registered = true;
            }

            if (! Route::has('session.keep-alive')) {
                Route::middleware(['web'])->group(function () {
                    Route::get('/session/keep-alive', [AuthController::class, 'keepAlive'])
                        ->name('session.keep-alive');
                });
                $registered = true;
            }

            if ($registered) {
                $routes = app('router')->getRoutes();
                $routes->refreshNameLookups();
                $routes->refreshActionLookups();
            }
        });

        // منقضی کردن خودکار فایل‌های کامپایل‌شده قدیمی Blade هنگام ارتقای نسخه
        // (به‌ویژه روی ویندوز که اختلاف ساعت آرشیو ZIP ممکن است تاریخ فایل Blade را قدیمی‌تر از کش نشان دهد)
        $compiledViewsDir = storage_path('framework/views');
        if (is_dir($compiledViewsDir)) {
            $versionMarker = $compiledViewsDir.'/.wagon_views_v41';
            $forceExpireAll = ! is_file($versionMarker);

            foreach (glob($compiledViewsDir.'/*.php') ?: [] as $compiledFile) {
                $compiledContent = @file_get_contents($compiledFile);
                $hasUnprotectedRoute = is_string($compiledContent)
                    && str_contains($compiledContent, "route('wagons.edit-owner'")
                    && ! str_contains($compiledContent, "Route::has('wagons.edit-owner')");

                if ($forceExpireAll || $hasUnprotectedRoute) {
                    @touch($compiledFile, 1);
                    clearstatcache(true, $compiledFile);
                }
            }

            if ($forceExpireAll) {
                @file_put_contents($versionMarker, 'v41');
            }
        }
    }

    /**
     * اگر کاربر پس از کپی فایل‌های جدید هنوز دستور php artisan migrate را اجرا نکرده باشد،
     * ستون‌ها و جدول تاریخچه ویرایش واگن (شماره واگن / صاحب کالا) را به‌صورت خودکار و ایمن ایجاد می‌کند.
     */
    private function ensureWagonOwnerSchema(): void
    {
        if (self::$schemaVerified) {
            return;
        }

        self::$schemaVerified = true;

        try {
            if (! Schema::hasTable('wagons')) {
                return;
            }

            if (Schema::hasTable('billings') && ! Schema::hasColumn('billings', 'receipt_number')) {
                Schema::table('billings', function (Blueprint $table) {
                    if (! Schema::hasColumn('billings', 'receipt_number')) {
                        $table->string('receipt_number', 100)->nullable()->after('total_amount');
                    }
                    if (! Schema::hasColumn('billings', 'receipt_date_shamsi')) {
                        $table->string('receipt_date_shamsi', 20)->nullable()->after('receipt_number');
                    }
                    if (! Schema::hasColumn('billings', 'bank_name')) {
                        $table->string('bank_name', 100)->nullable()->after('receipt_date_shamsi');
                    }
                    if (! Schema::hasColumn('billings', 'receipt_amount')) {
                        $table->unsignedBigInteger('receipt_amount')->nullable()->after('bank_name');
                    }
                    if (! Schema::hasColumn('billings', 'settled_at')) {
                        $table->timestamp('settled_at')->nullable()->after('receipt_amount');
                    }
                });
            }

            if (! Schema::hasColumn('wagons', 'cargo_description')) {
                Schema::table('wagons', function (Blueprint $table) {
                    $table->string('cargo_description', 255)->nullable()->after('wagon_number');
                });
            }

            if (! Schema::hasColumn('wagons', 'line_number')) {
                Schema::table('wagons', function (Blueprint $table) {
                    $table->string('line_number', 50)->nullable()->after('cargo_description');
                });
            }

            if (! Schema::hasColumn('wagons', 'owner_change_count') || ! Schema::hasColumn('wagons', 'previous_wagon_number')) {
                Schema::table('wagons', function (Blueprint $table) {
                    if (! Schema::hasColumn('wagons', 'owner_id')) {
                        $table->foreignId('owner_id')->nullable()->after('cargo_request_id')->constrained('owners')->nullOnDelete();
                    }
                    if (! Schema::hasColumn('wagons', 'previous_owner_id')) {
                        $table->foreignId('previous_owner_id')->nullable()->after('owner_id')->constrained('owners')->nullOnDelete();
                    }
                    if (! Schema::hasColumn('wagons', 'previous_wagon_number')) {
                        $table->string('previous_wagon_number', 50)->nullable()->after('wagon_number');
                    }
                    if (! Schema::hasColumn('wagons', 'owner_change_note')) {
                        $table->text('owner_change_note')->nullable()->after('is_settled');
                    }
                    if (! Schema::hasColumn('wagons', 'owner_changed_at')) {
                        $table->timestamp('owner_changed_at')->nullable()->after('owner_change_note');
                        $table->index('owner_changed_at');
                    }
                    if (! Schema::hasColumn('wagons', 'owner_changed_by')) {
                        $table->foreignId('owner_changed_by')->nullable()->after('owner_changed_at')->constrained('users')->nullOnDelete();
                    }
                    if (! Schema::hasColumn('wagons', 'owner_change_count')) {
                        $table->unsignedInteger('owner_change_count')->default(0)->after('owner_changed_by');
                    }
                });

                $requests = DB::table('cargo_requests')->pluck('owner_id', 'id');

                foreach ($requests as $requestId => $ownerId) {
                    DB::table('wagons')
                        ->where('cargo_request_id', $requestId)
                        ->whereNull('owner_id')
                        ->update(['owner_id' => $ownerId]);
                }
            }

            if (! Schema::hasTable('wagon_owner_logs')) {
                Schema::create('wagon_owner_logs', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('wagon_id')->constrained('wagons')->cascadeOnDelete();
                    $table->foreignId('billing_id')->nullable()->constrained('billings')->nullOnDelete();
                    $table->string('change_type', 30)->default('owner');
                    $table->string('old_wagon_number', 50)->nullable();
                    $table->string('new_wagon_number', 50)->nullable();
                    $table->foreignId('old_owner_id')->nullable()->constrained('owners')->nullOnDelete();
                    $table->foreignId('new_owner_id')->constrained('owners')->restrictOnDelete();
                    $table->string('old_owner_name');
                    $table->string('new_owner_name');
                    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                    $table->text('note');
                    $table->timestamps();

                    $table->index(['wagon_id', 'created_at']);
                    $table->index('created_at');
                });
            } else {
                Schema::table('wagon_owner_logs', function (Blueprint $table) {
                    if (! Schema::hasColumn('wagon_owner_logs', 'change_type')) {
                        $table->string('change_type', 30)->default('owner')->after('billing_id');
                    }
                    if (! Schema::hasColumn('wagon_owner_logs', 'old_wagon_number')) {
                        $table->string('old_wagon_number', 50)->nullable()->after('change_type');
                    }
                    if (! Schema::hasColumn('wagon_owner_logs', 'new_wagon_number')) {
                        $table->string('new_wagon_number', 50)->nullable()->after('old_wagon_number');
                    }
                });
            }

            // اختیاری کردن owner_id در جدول cargo_requests و old_owner_id در wagon_owner_logs برای دیتابیس‌های موجود
            $nullableOwnerMarker = storage_path('framework/.nullable_owner_v1');
            if (! is_file($nullableOwnerMarker)) {
                $driver = DB::getDriverName();
                if ($driver === 'mysql') {
                    if (Schema::hasTable('cargo_requests') && Schema::hasColumn('cargo_requests', 'owner_id')) {
                        DB::statement('ALTER TABLE `cargo_requests` MODIFY `owner_id` BIGINT UNSIGNED NULL');
                    }
                    if (Schema::hasTable('wagon_owner_logs') && Schema::hasColumn('wagon_owner_logs', 'old_owner_id')) {
                        DB::statement('ALTER TABLE `wagon_owner_logs` MODIFY `old_owner_id` BIGINT UNSIGNED NULL');
                    }
                }
                @file_put_contents($nullableOwnerMarker, '1');
            }
            $seededMarker = storage_path('framework/.setad_tariffs_seeded_v1');
            if (! is_file($seededMarker) && Schema::hasTable('tariffs') && class_exists(TariffSeeder::class)) {
                $hasSetadTariffs = DB::table('tariffs')->where('code', 'T-1405-01')->exists();
                if (! $hasSetadTariffs) {
                    foreach (TariffSeeder::defaultTariffs() as $t) {
                        Tariff::updateOrCreate(['code' => $t['code']], $t + ['is_active' => true]);
                    }
                }
                @file_put_contents($seededMarker, '1');
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
