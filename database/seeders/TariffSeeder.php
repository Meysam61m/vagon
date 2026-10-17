<?php

namespace Database\Seeders;

use App\Models\Tariff;
use Illuminate\Database\Seeder;

class TariffSeeder extends Seeder
{
    /**
     * تعرفه‌های نمونه؛ اعداد را با بخشنامه جاری راه‌آهن جایگزین کنید.
     */
    public function run(): void
    {
        $tariffs = [
            ['code' => 'T-101', 'title' => 'تخلیه/بارگیری واگن سرباز با جرثقیل', 'unit' => 'واگن - ۶۰ تن', 'rate' => 4_500_000, 'capacity' => 60, 'category' => 1, 'equipment_type' => 'crane'],
            ['code' => 'T-102', 'title' => 'تخلیه/بارگیری واگن لبه‌بلند با جرثقیل', 'unit' => 'واگن - ۶۵ تن', 'rate' => 5_200_000, 'capacity' => 65, 'category' => 1, 'equipment_type' => 'crane'],
            ['code' => 'T-201', 'title' => 'بارگیری واگن با کارگر', 'unit' => 'واگن - ۶۰ تن', 'rate' => 2_800_000, 'capacity' => 60, 'category' => 2, 'equipment_type' => 'labor'],
            ['code' => 'T-202', 'title' => 'تخلیه واگن با کارگر', 'unit' => 'واگن - ۶۰ تن', 'rate' => 2_300_000, 'capacity' => 60, 'category' => 2, 'equipment_type' => 'labor'],
            ['code' => 'T-301', 'title' => 'جابه‌جایی بار با لیفتراک', 'unit' => 'واگن - ۶۵ تن', 'rate' => 3_600_000, 'capacity' => 65, 'category' => 2, 'equipment_type' => 'forklift'],
        ];

        foreach ($tariffs as $tariff) {
            Tariff::updateOrCreate(['code' => $tariff['code']], $tariff + ['is_active' => true]);
        }
    }
}
