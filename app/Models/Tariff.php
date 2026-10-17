<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    public const CATEGORY_LABELS = [
        1 => 'تخلیه/بارگیری مکانیزه',
        2 => 'عملیات دستی',
        3 => 'سایر خدمات',
    ];

    public const EQUIPMENT_LABELS = [
        'crane' => 'جرثقیل',
        'labor' => 'کارگری',
        'forklift' => 'لیفتراک',
    ];

    protected $fillable = [
        'code', 'rate', 'unit', 'title', 'category', 'capacity', 'equipment_type', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rate' => 'integer',
            'capacity' => 'decimal:2',
            'category' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.trim($term).'%';

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', $term)
                ->orWhere('code', 'like', $term);
        });
    }

    public function equipmentLabel(): string
    {
        return self::EQUIPMENT_LABELS[$this->equipment_type] ?? $this->equipment_type;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? ('دسته '.$this->category);
    }
}
