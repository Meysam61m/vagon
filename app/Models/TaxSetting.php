<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = ['percentage', 'is_current', 'updated_by'];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'percentage' => 'decimal:2',
        ];
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->latest('id')->first();
    }

    /** درصد مالیات جاری سیستم (اگر تنظیم نشده باشد صفر) */
    public static function currentPercentage(): float
    {
        return (float) (static::where('is_current', true)->latest('id')->value('percentage') ?? 0);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
