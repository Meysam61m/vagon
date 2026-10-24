<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * ۱. اگر کاربر در میانه نشست غیرفعال شود، همان درخواست بعدی او را بیرون می‌اندازد.
 * ۲. اگر بیش از ۳۰ دقیقه (۱۸۰۰ ثانیه) عدم فعالیت در نشست ثبت شده باشد، کاربر را به‌طور خودکار خارج می‌کند.
 */
class EnsureUserIsActive
{
    private const IDLE_TIMEOUT_SECONDS = 30 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // حذف کوکی دائمی قدیمی «مرا به خاطر بسپار» (در صورت وجود از نسخه‌های قبل) تا با بستن مرورگر لاگین مجدد اجباری باشد
            $recallerName = Auth::guard('web')->getRecallerName();
            if ($request->hasCookie($recallerName)) {
                Cookie::queue(Cookie::forget($recallerName));
            }

            if (! $user->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'حساب کاربری شما غیرفعال شده است.']);
            }

            $now = time();
            $lastActivity = (int) $request->session()->get('wagon_last_activity_at', 0);

            if ($lastActivity > 0 && ($now - $lastActivity) > self::IDLE_TIMEOUT_SECONDS) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('warning', 'به دلیل ۳۰ دقیقه عدم فعالیت، به صورت خودکار از سامانه خارج شدید. لطفاً مجدداً وارد شوید.');
            }

            $request->session()->put('wagon_last_activity_at', $now);
        }

        return $next($request);
    }
}
