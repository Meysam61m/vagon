<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
