<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class JalaliDateTest extends TestCase
{
    public static function normalizationProvider(): array
    {
        return [
            'ارقام فارسی' => ['۱۴۰۴/۰۷/۲۵', '1404/07/25'],
            'ارقام عربی' => ['١٤٠٤/٧/٥', '1404/07/05'],
            'خط تیره' => ['1404-7-25', '1404/07/25'],
            'فاصله' => [' 1404 07 25 ', '1404/07/25'],
            'خالی' => ['', null],
        ];
    }

    #[DataProvider('normalizationProvider')]
    public function test_normalizes_digits_and_separators(string $input, ?string $expected): void
    {
        $this->assertSame($expected, JalaliDate::normalize($input));
    }

    public function test_rejects_invalid_dates(): void
    {
        $this->assertFalse(JalaliDate::isValidShamsi('1404/13/01'));
        $this->assertFalse(JalaliDate::isValidShamsi('1404/07/32'));
        $this->assertFalse(JalaliDate::isValidShamsi('1404/12/30')); // ۱۴۰۴ کبیسه نیست
        $this->assertTrue(JalaliDate::isValidShamsi('1404/12/29'));
        $this->assertTrue(JalaliDate::isValidShamsi('1403/12/30')); // ۱۴۰۳ کبیسه است
        $this->assertTrue(JalaliDate::isValidShamsi('1404/06/31'));
        $this->assertFalse(JalaliDate::isValidShamsi('1404/07/00'));
        $this->assertFalse(JalaliDate::isValidShamsi('hello'));
    }

    public function test_parse_throws_validation_exception_for_bad_input(): void
    {
        $this->expectException(ValidationException::class);

        JalaliDate::parse('1404/13/10', 'from');
    }

    public function test_parses_and_formats_known_date(): void
    {
        $carbon = JalaliDate::parse('1404/07/25');

        $this->assertSame('2025-10-17', $carbon->toDateString());
        $this->assertSame('1404/07/25', JalaliDate::fromCarbon($carbon));
    }
}
