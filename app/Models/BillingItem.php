<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingItem extends Model
{
    protected $fillable = [
        'billing_id', 'wagon_id', 'tariff_id', 'tariff_title', 'tariff_rate',
        'tariff_capacity', 'loaded_weight', 'base_cost', 'tax_amount', 'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'tariff_rate' => 'integer',
            'tariff_capacity' => 'decimal:2',
            'loaded_weight' => 'decimal:2',
            'base_cost' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }

    public function wagon()
    {
        return $this->belongsTo(Wagon::class);
    }

    public function tariff()
    {
        return $this->belongsTo(Tariff::class);
    }

    /**
     * هزینه پایه یک ردیف صورتحساب:
     * (وزن بارگیری‌شده ÷ ظرفیت واگن) × نرخ کامل تعرفه
     *
     * اعتبارسنجی «وزن بیشتر از ظرفیت» در کنترلر انجام می‌شود تا این فرمول
     * بدون سقف پنهان باقی بماند.
     */
    public static function calculateBaseCost(float $loadedWeight, float $capacity, float $rate): float
    {
        if ($capacity <= 0 || $loadedWeight <= 0) {
            return 0;
        }

        return round(($loadedWeight / $capacity) * $rate);
    }

    /** سهم این ردیف از مالیات فاکتور (بر اساس همان درصد لحظه صدور) */
    public static function calculateTax(float $baseCost, float $taxPercentage): float
    {
        return round($baseCost * ($taxPercentage / 100));
    }
}
