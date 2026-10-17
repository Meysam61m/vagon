<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

/**
 * تولید شماره اسناد (فاکتور/درخواست) به‌صورت اتمی.
 *
 * شماره‌های حذف‌شده (soft delete) هم در محاسبه لحاظ می‌شوند و ستون شماره
 * ایندکس یکتا دارد؛ بنابراین حتی با درخواست هم‌زمان دو شماره یکسان ساخته نمی‌شود.
 */
class DocumentNumber
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public static function next(string $modelClass, string $column, string $prefix): string
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $modelClass::query();

        if (method_exists($modelClass, 'withTrashed')) {
            $query = $query->withTrashed();
        }

        $today = Jalalian::now()->format('ymd');
        $like = $prefix.'-'.$today.'-%';

        $last = $query
            ->where($column, 'like', $like)
            ->orderByDesc($column)
            ->lockForUpdate()
            ->value($column);

        $next = 1;

        if (is_string($last) && preg_match('/-(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $today, $next);
    }

    /**
     * آیا خطا مربوط به نقض قید یکتایی است؟ (برای تلاش مجدد در تصادم شماره)
     */
    public static function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $message = $exception->getMessage();

        return $sqlState === '23000'
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry');
    }

    /**
     * اجرای یک عملیات با تلاش مجدد در صورت تصادم شماره سند.
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function withRetry(callable $callback, int $attempts = 3): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (QueryException $exception) {
                if ($attempt >= $attempts || ! self::isUniqueViolation($exception)) {
                    throw $exception;
                }

                usleep(20_000 * $attempt);
            }
        }
    }
}
