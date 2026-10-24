<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

/**
 * تمام تبدیل‌های تاریخ شمسی/میلادی از این کلاس عبور می‌کنند تا:
 *  - ارقام فارسی/عربی و جداکننده‌های مختلف (- ، فاصله) پذیرفته شوند،
 *  - تاریخ نامعتبر به‌جای جایگزینی خاموش، خطای اعتبارسنجی فارسی بدهد،
 *  - ذخیره‌سازی یکدست (YYYY/MM/DD با صفر ابتدایی) باشد.
 */
class JalaliDate
{
    public const DISPLAY_FORMAT = 'Y/m/d';

    /** ارقام فارسی/عربی → لاتین */
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '،' => ',',
    ];

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(strtr($value, self::DIGIT_MAP));

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[\/\-\s\.]+/u', '/', $value) ?? $value;

        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $value, $matches)) {
            return sprintf('%04d/%02d/%02d', $matches[1], $matches[2], $matches[3]);
        }

        return $value;
    }

    public static function isValidShamsi(?string $value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === null || ! preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $normalized, $matches)) {
            return false;
        }

        [$year, $month, $day] = [(int) $matches[1], (int) $matches[2], (int) $matches[3]];

        if ($year < 1200 || $year > 1700 || $month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        if ($month <= 6) {
            return $day <= 31;
        }

        if ($month <= 11) {
            return $day <= 30;
        }

        return $day <= (self::isLeapYear($year) ? 30 : 29);
    }

    /**
     * سال کبیسه شمسی (چرخه ۳۳ ساله؛ برای بازه ۱۳۴۳ تا ۱۴۷۲ دقیق است).
     */
    public static function isLeapYear(int $year): bool
    {
        return in_array($year % 33, [1, 5, 9, 13, 17, 22, 26, 30], true);
    }

    /**
     * رشته شمسی را به تاریخ میلادی تبدیل می‌کند و در صورت نامعتبر بودن،
     * خطای اعتبارسنجی فارسی با کلید مشخص پرتاب می‌کند.
     *
     * @throws ValidationException
     */
    public static function parse(?string $value, string $field = 'date'): Carbon
    {
        $normalized = self::normalize($value);

        if (! self::isValidShamsi($normalized)) {
            throw ValidationException::withMessages([
                $field => 'تاریخ شمسی وارد شده معتبر نیست. قالب درست: ۱۴۰۴/۰۷/۲۵',
            ]);
        }

        return Carbon::instance(
            Jalalian::fromFormat(self::DISPLAY_FORMAT, $normalized)->toCarbon()
        )->startOfDay();
    }

    public static function tryParse(?string $value): ?Carbon
    {
        try {
            return self::parse($value);
        } catch (ValidationException) {
            return null;
        }
    }

    public static function fromCarbon(?CarbonInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return Jalalian::fromCarbon(Carbon::instance($date))->format(self::DISPLAY_FORMAT);
    }

    public static function today(string $format = self::DISPLAY_FORMAT): string
    {
        return Jalalian::fromCarbon(now())->format($format);
    }

    /** نمایش تاریخ میلادی ذخیره‌شده به شمسی (برای گزارش‌های قدیمی) */
    public static function display(?string $gregorianDate): string
    {
        if (blank($gregorianDate)) {
            return '-';
        }

        try {
            return self::fromCarbon(Carbon::parse($gregorianDate)) ?? '-';
        } catch (\Throwable $e) {
            report($e);

            return (string) $gregorianDate;
        }
    }
}
