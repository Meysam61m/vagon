<?php

namespace Database\Seeders;

use App\Models\Tariff;
use Illuminate\Database\Seeder;

class TariffSeeder extends Seeder
{
    /**
     * فهرست تعرفه‌های کتابچه عملیات فاز ۲ شهید مطهری (سال ۱۴۰۵ و ۱۴۰۴).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultTariffs(): array
    {
        return [
            // تعرفه‌های جاری ۱۴۰۵ — عملیات با جرثقیل
            [
                'code' => 'T-1405-01',
                'title' => 'بارگیری/تخلیه آهن‌آلات (میلگرد، کلاف، ناودونی، نبشی، تسمه، سوله، شیشه) با جرثقیل',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 175_062_991,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'crane',
            ],
            [
                'code' => 'T-1405-02',
                'title' => 'بارگیری/تخلیه سود پرک با جرثقیل',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 184_162_409,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'crane',
            ],
            [
                'code' => 'T-1405-03',
                'title' => 'بارگیری/تخلیه سیمان جامبو با جرثقیل',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 172_355_865,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'crane',
            ],

            // تعرفه‌های جاری ۱۴۰۵ — عملیات با لیفتراک
            [
                'code' => 'T-1405-04',
                'title' => 'بارگیری/تخلیه کاشی، چینی، شیرخشک، آبمیوه و کابل با لیفتراک',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 120_117_393,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],
            [
                'code' => 'T-1405-05',
                'title' => 'بارگیری/تخلیه سنگ و سنگ ساختمانی با لیفتراک',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 156_666_490,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],
            [
                'code' => 'T-1405-06',
                'title' => 'بارگیری/تخلیه کروم و جوش شیرین با لیفتراک',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 161_288_769,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],
            [
                'code' => 'T-1405-07',
                'title' => 'بارگیری/تخلیه جوش شیرین (نوع ۲) با لیفتراک',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 182_931_438,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],
            [
                'code' => 'T-1405-08',
                'title' => 'بارگیری/تخلیه ام دی اف (MDF) با لیفتراک',
                'unit' => 'واگن - ۷۰ تن',
                'rate' => 195_994_672,
                'capacity' => 70,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],
            [
                'code' => 'T-1405-09',
                'title' => 'بارگیری/تخلیه رزین با لیفتراک',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 132_225_589,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],

            // تعرفه‌های سال ۱۴۰۴ (بدهی سال قبل)
            [
                'code' => 'T-1404-01',
                'title' => 'بارگیری/تخلیه میلگرد، کلاف و شیشه با جرثقیل (تعرفه ۱۴۰۴)',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 111_904_864,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'crane',
            ],
            [
                'code' => 'T-1404-02',
                'title' => 'بارگیری/تخلیه متعلقات دستگاه با جرثقیل (تعرفه ۱۴۰۴)',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 110_067_448,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'crane',
            ],
            [
                'code' => 'T-1404-03',
                'title' => 'بارگیری/تخلیه سنگ با جرثقیل (تعرفه ۱۴۰۴)',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 247_164_812,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'crane',
            ],
            [
                'code' => 'T-1404-04',
                'title' => 'بارگیری/تخلیه سنگ با لیفتراک (تعرفه ۱۴۰۴)',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 108_773_450,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],
            [
                'code' => 'T-1404-05',
                'title' => 'بارگیری/تخلیه اسید استیک با لیفتراک (تعرفه ۱۴۰۴)',
                'unit' => 'واگن - ۶۵ تن',
                'rate' => 76_881_415,
                'capacity' => 65,
                'category' => 1,
                'equipment_type' => 'forklift',
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::defaultTariffs() as $tariff) {
            Tariff::updateOrCreate(['code' => $tariff['code']], $tariff + ['is_active' => true]);
        }
    }
}
