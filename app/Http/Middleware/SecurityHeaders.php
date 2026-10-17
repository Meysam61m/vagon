<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * هدرهای امنیتی استاندارد برای همه پاسخ‌های وب.
 *
 * نکته: در محیط production هدرهای ضد iframe (X-Frame-Options و
 * frame-ancestors) هم ارسال می‌شوند تا حمله clickjacking ممکن نباشد.
 * در محیط local این دو هدر ارسال نمی‌شوند تا پیش‌نمایش داخل iframe کار کند.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return self::apply($next($request), $request);
    }

    /**
     * اعمال هدرها روی یک پاسخ.
     *
     * این متد از دو جا صدا زده می‌شود: میدل‌ور (پاسخ‌های عادی) و
     * exception handler (صفحه‌های خطا مثل 404/419/500 که میدل‌ور وب
     * روی آن‌ها اجرا نمی‌شود).
     */
    public static function apply(Response $response, ?Request $request = null): Response
    {
        $request ??= request();

        $headers = $response->headers;

        // جلوگیری از حدس نوع فایل توسط مرورگر (MIME sniffing)
        $headers->set('X-Content-Type-Options', 'nosniff');

        // نشت اطلاعات در Referer به سایت‌های خارجی
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // دسترسی به دوربین/میکروفن/موقعیت‌مکانی لازم نیست
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        /*
         * سیاست امنیت محتوا: همه دارایی‌ها محلی هستند. اسکریپت/استایل درون‌خطی
         * (datepicker و window.print) نیاز به 'unsafe-inline' دارد؛ با این حال
         * object-src/base-uri/form-action محدود می‌شوند که جلوی تزریق
         * پلاگین و تغییر base را می‌گیرد.
         */
        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "img-src 'self' data:",
                "font-src 'self' data:",
                "style-src 'self' 'unsafe-inline'",
                "script-src 'self' 'unsafe-inline'",
                "connect-src 'self'",
                "form-action 'self'",
                app()->environment('production') ? "frame-ancestors 'self'" : "frame-ancestors *",
            ]));
        }

        // صفحه‌های داخلی نباید در موتور جست‌وجو ایندکس شوند
        $headers->set('X-Robots-Tag', 'noindex, nofollow');

        if (app()->environment('production')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');

            // HSTS فقط روی HTTPS و فقط در production
            if ($request->secure()) {
                $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        return $response;
    }
}
