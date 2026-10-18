<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// اگر کش مسیرها (routes-v7.php) از نسخه قبلی باقی مانده و مسیرهای جدید را ندارد،
// پیش از راه‌اندازی مسیریاب حذف شود تا routes/web.php بارگذاری گردد.
$wagonRouteCache = dirname(__DIR__).'/bootstrap/cache/routes-v7.php';
if (is_file($wagonRouteCache)) {
    $wagonRouteCacheContent = @file_get_contents($wagonRouteCache);
    if ($wagonRouteCacheContent === false || ! str_contains($wagonRouteCacheContent, 'wagons.edit-owner')) {
        @unlink($wagonRouteCache);
        clearstatcache(true, $wagonRouteCache);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ثبت میان‌افزار نقش برای استفاده در روت‌ها به صورت 'role:admin'
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        /*
         * کاربران غیرفعال حتی با سشن معتبر هم بلافاصله بیرون انداخته می‌شوند.
         * هدرهای امنیتی هم روی همه پاسخ‌های وب اعمال می‌شوند.
         */
        $middleware->web(append: [
            EnsureUserIsActive::class,
            SecurityHeaders::class,
        ]);

        // پشت پروکسی/CDN: TRUSTED_PROXIES در config/app.php خوانده و در
        // AppServiceProvider::boot() اعمال می‌شود.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         * صفحه‌های خطا (404، 419، 500 و ...) از میدل‌ور وب عبور نمی‌کنند؛
         * برای همین هدرهای امنیتی را روی پاسخ آن‌ها هم اعمال می‌کنیم.
         */
        $exceptions->respond(
            fn (\Symfony\Component\HttpFoundation\Response $response) => SecurityHeaders::apply($response)
        );
    })->create();
