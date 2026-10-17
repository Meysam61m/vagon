<?php

namespace Database\Seeders;

use App\Models\TaxSetting;
use Illuminate\Database\Seeder;

class TaxSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (TaxSetting::where('is_current', true)->exists()) {
            return;
        }

        TaxSetting::create([
            'percentage' => 9,
            'is_current' => true,
        ]);
    }
}
