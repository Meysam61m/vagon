<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ثبت در bootstrap/app.php:
 * ->withMiddleware(function (Middleware $middleware) {
 *     $middleware->alias(['role' => \App\Http\Middleware\CheckRole::class]);
 * })
 *
 * استفاده در روت: Route::middleware('role:admin')->group(...)
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'حساب کاربری شما غیرفعال است.');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'شما دسترسی لازم برای این بخش را ندارید.');
        }

        return $next($request);
    }
}
