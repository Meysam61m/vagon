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
    if (
        $wagonRouteCacheContent === false
        || ! str_contains($wagonRouteCacheContent, 'wagons.edit-owner')
        || ! str_contains($wagonRouteCacheContent, 'reports.setad')
        || ! str_contains($wagonRouteCacheContent, 'tariffs.destroy')
        || ! str_contains($wagonRouteCacheContent, 'cargo.quick-bill')
        || ! str_contains($wagonRouteCacheContent, 'home')
    ) {
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

        // هدایت کاربران لاگین‌شده (هنگام مراجعه به /login) بر اساس نقش: مدیر به داشبورد، اپراتور به کارتابل بارگیری
        $middleware->redirectUsersTo(
            fn () => auth()->user()?->isAdmin() ? route('dashboard') : route('cargo.index')
        );

        // خروج از سامانه حتی در صورت منقضی شدن توکن CSRF نباید خطای 419 بدهد
        $middleware->validateCsrfTokens(except: [
            'logout',
            '/logout',
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
         * مدیریت هوشمند انقضای سشن / توکن CSRF (خطای 419) بدون نمایش صفحه خطای سفید
         * و اعمال هدرهای امنیتی روی تمام پاسخ‌ها
         */
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if ($response->getStatusCode() === 419 || $e instanceof \Illuminate\Session\TokenMismatchException) {
                if ($request->hasSession()) {
                    $request->session()->regenerateToken();
                }

                if ($request->is('logout') || $request->routeIs('logout')) {
                    return SecurityHeaders::apply(
                        redirect()->route('login')->with('info', 'نشست کاری شما پایان یافته بود و از سامانه خارج شدید.')
                    );
                }

                if ($request->is('login') || $request->routeIs('login.submit')) {
                    return SecurityHeaders::apply(
                        redirect()->route('login')
                            ->withInput($request->only('email', 'remember'))
                            ->with('warning', 'صفحه ورود برای مدت طولانی باز مانده بود و نشست تازه شد. لطفاً رمز عبور را وارد کرده و مجدداً دکمه ورود را بزنید.')
                    );
                }

                return SecurityHeaders::apply(
                    redirect()->back(fallback: route('login'))
                        ->withInput($request->except('password', 'password_confirmation', '_token'))
                        ->with('warning', 'نشست کاری شما به دلیل عدم فعالیت طولانی‌مدت تازه شد. لطفاً مجدداً تلاش کنید.')
                );
            }

            return SecurityHeaders::apply($response);
        });
    })->create();
