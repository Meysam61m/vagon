<?php

namespace App\Services;

use App\Models\BillingItem;
use App\Models\Tariff;
use App\Models\Wagon;
use Illuminate\Validation\ValidationException;

final class BillingCalculationService
{
    /**
     * محاسبه مبالغ پایه، مالیات بر ارزش افزوده و مبلغ کل واگن بر اساس کتابچه تعرفه و تناژ عملیاتی
     *
     * @return array{
     *     capacity: float,
     *     operational_tonnage: float,
     *     booklet_rate: float,
     *     per_ton_rate: float,
     *     booklet_with_vat: int,
     *     base_cost: int,
     *     tax_amount: int,
     *     total_amount: int,
     *     vat_divisor: float,
     *     is_tonnage: bool
     * }
     */
    public function calculateWagonAmounts(
        ?Tariff $tariff,
        ?float $loadedWeight,
        float $taxPct,
        ?float $overrideRate = null,
        ?float $overrideCapacity = null
    ): array {
        $capacity = (float) ($overrideCapacity ?: ($tariff?->capacity ?: 65.0));
        if ($capacity <= 0) {
            $capacity = 65.0;
        }

        $operationalTonnage = (float) ($loadedWeight && $loadedWeight > 0 ? $loadedWeight : $capacity);
        $bookletRate = (float) ($overrideRate !== null && $overrideRate > 0 ? $overrideRate : ($tariff?->rate ?: 0.0));
        $perTonRate = $bookletRate / $capacity;

        $bookletBaseRounded = (int) round($bookletRate);
        $bookletTaxRounded = (int) BillingItem::calculateTax($bookletBaseRounded, $taxPct);
        $bookletWithVat = $bookletBaseRounded + $bookletTaxRounded;

        $baseCost = (int) BillingItem::calculateBaseCost($operationalTonnage, $capacity, $bookletRate);
        $taxAmount = (int) BillingItem::calculateTax($baseCost, $taxPct);
        $totalAmount = $baseCost + $taxAmount;

        $vatDivisor = $taxPct >= 0 ? round(1 + ($taxPct / 100), 6) : 1.0;
        if ($vatDivisor <= 0) {
            $vatDivisor = 1.0;
        }

        return [
            'capacity' => $capacity,
            'operational_tonnage' => $operationalTonnage,
            'booklet_rate' => $bookletRate,
            'per_ton_rate' => $perTonRate,
            'booklet_with_vat' => $bookletWithVat,
            'base_cost' => $baseCost,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'vat_divisor' => $vatDivisor,
            'is_tonnage' => ($capacity - $operationalTonnage) >= 0.01,
        ];
    }

    /**
     * اعتبارسنجی تناژ واگن در برابر ظرفیت تعرفه و ساخت ساختار آیتم صورتحساب
     *
     * @return array{
     *     wagon_id: int,
     *     tariff_id: int,
     *     tariff_title: string,
     *     tariff_rate: int|float,
     *     tariff_capacity: int|float,
     *     loaded_weight: float,
     *     base_cost: int,
     *     tax_amount: int,
     *     total_amount: int
     * }
     */
    public function buildBillingItemPayload(
        Wagon $wagon,
        Tariff $tariff,
        float $weight,
        float $taxPercentage,
        string $errorKey = 'wagons'
    ): array {
        if ($weight > (float) $tariff->capacity) {
            throw ValidationException::withMessages([
                $errorKey => "وزن بارگیری واگن {$wagon->wagon_number} ({$weight} تن) نمی‌تواند بیشتر از ظرفیت تعرفه ({$tariff->capacity} تن) باشد.",
            ]);
        }

        $baseCost = BillingItem::calculateBaseCost($weight, (float) $tariff->capacity, (float) $tariff->rate);
        $itemTax = BillingItem::calculateTax($baseCost, $taxPercentage);

        return [
            'wagon_id' => $wagon->id,
            'tariff_id' => $tariff->id,
            'tariff_title' => $tariff->title,
            'tariff_rate' => $tariff->rate,
            'tariff_capacity' => $tariff->capacity,
            'loaded_weight' => $weight,
            'base_cost' => $baseCost,
            'tax_amount' => $itemTax,
            'total_amount' => $baseCost + $itemTax,
        ];
    }
}
