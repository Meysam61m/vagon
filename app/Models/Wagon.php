<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wagon extends Model
{
    protected $fillable = [
        'cargo_request_id', 'wagon_number', 'cargo_description', 'tariff_id', 'loaded_weight', 'loaded_on', 'is_settled',
    ];

    protected function casts(): array
    {
        return [
            'is_settled' => 'boolean',
            'loaded_weight' => 'decimal:2',
            'loaded_on' => 'date',
        ];
    }

    public function cargoRequest()
    {
        return $this->belongsTo(CargoRequest::class);
    }

    public function tariff()
    {
        return $this->belongsTo(Tariff::class);
    }

    public function billingItem()
    {
        return $this->hasOne(BillingItem::class);
    }

    public function isLoaded(): bool
    {
        return $this->loaded_weight !== null;
    }

    public function scopePendingSettlement($query)
    {
        return $query->where('is_settled', false);
    }

    public function scopeLoaded($query)
    {
        return $query->whereNotNull('loaded_weight');
    }
}
