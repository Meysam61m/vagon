<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
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

        $credentials['email'] = mb_strtolower(trim($credentials['email']));

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "تلاش‌های ناموفق زیاد بود. لطفاً {$seconds} ثانیه دیگر دوباره تلاش کنید.",
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['email' => 'ایمیل یا رمز عبور اشتباه است.'])
                ->onlyInput('email');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'حساب کاربری شما غیرفعال شده است.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $defaultHome = Auth::user()->isAdmin() ? route('dashboard') : route('cargo.index');

        return redirect()->intended($defaultHome);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
