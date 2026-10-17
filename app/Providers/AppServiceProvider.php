<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Morilog\Jalali\Jalalian;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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

        // اطمینان از اینکه نمونه‌سازی کلاس تاریخ شمسی در زمان اجرا با autoload کامپوزر انجام می‌شود
        class_exists(Jalalian::class);
    }
}
