<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function home()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return redirect()->route(Auth::user()->isAdmin() ? 'dashboard' : 'cargo.index');
    }

    public function showLogin()
    {
        if (Auth::check() && Auth::user()?->is_active) {
            return redirect()->route(Auth::user()->isAdmin() ? 'dashboard' : 'cargo.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
        ]);

        $rawEmail = trim((string) $credentials['email']);
        $credentials['email'] = function_exists('mb_strtolower')
            ? mb_strtolower($rawEmail, 'UTF-8')
            : strtolower($rawEmail);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "تلاش‌های ناموفق زیاد بود. لطفاً {$seconds} ثانیه دیگر دوباره تلاش کنید.",
            ])->onlyInput('email');
        }

        // اگر کاربر دیگری در همین مرورگر لاگین بوده، ابتدا نشست قبلی را پاک کن
        if (Auth::check()) {
            Auth::guard('web')->logout();
        }

        // عدم استفاده از کوکی دائمی remember تا با بستن مرورگر لاگین مجدد اجباری باشد
        if (! Auth::attempt($credentials, false)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['email' => 'ایمیل یا رمز عبور اشتباه است.'])
                ->onlyInput('email');
        }

        Cookie::queue(Cookie::forget(Auth::guard('web')->getRecallerName()));

        if (! Auth::user()->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'حساب کاربری شما غیرفعال شده است.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->session()->put('wagon_last_activity_at', time());

        $user = Auth::user();
        $defaultHome = $user->isAdmin() ? route('dashboard') : route('cargo.index');

        // بررسی امن بودن آدرس intended قبلی در سشن (جلوگیری از هدایت به خروج یا صفحات غیرمجاز نقش دیگر)
        $intended = (string) $request->session()->get('url.intended', '');
        if ($intended !== '') {
            $intendedPath = '/'.ltrim((string) parse_url($intended, PHP_URL_PATH), '/');
            $isInvalidTarget = str_starts_with($intendedPath, '/login')
                || str_starts_with($intendedPath, '/logout')
                || str_starts_with($intendedPath, '/session/');

            $isAdminOnlyTarget = str_starts_with($intendedPath, '/dashboard')
                || str_starts_with($intendedPath, '/users')
                || str_starts_with($intendedPath, '/tariffs')
                || str_starts_with($intendedPath, '/tax');

            if ($isInvalidTarget || (! $user->isAdmin() && $isAdminOnlyTarget)) {
                $request->session()->forget('url.intended');
            }
        }

        return redirect()->intended($defaultHome);
    }

    public function logout(Request $request)
    {
        Cookie::queue(Cookie::forget(Auth::guard('web')->getRecallerName()));

        if (Auth::check()) {
            Auth::guard('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->query('reason') === 'idle') {
            return redirect()
                ->route('login')
                ->with('warning', 'به دلیل ۳۰ دقیقه عدم فعالیت، به صورت خودکار از سامانه خارج شدید. لطفاً مجدداً وارد شوید.');
        }

        return redirect()->route('login')->with('info', 'شما با موفقیت از سامانه خارج شدید.');
    }

    /**
     * تمدید خودکار نشست کاری (Heartbeat) برای جلوگیری از منقضی شدن فرم‌ها و صفحه ورود
     */
    public function keepAlive(Request $request)
    {
        return response()->json([
            'ok' => true,
            'authenticated' => Auth::check(),
            'token' => csrf_token(),
        ]);
    }
}
