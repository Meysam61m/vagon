<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wagon extends Model
{
    protected $fillable = [
        'cargo_request_id',
        'owner_id',
        'previous_owner_id',
        'wagon_number',
        'cargo_description',
        'tariff_id',
        'loaded_weight',
        'loaded_on',
        'is_settled',
        'owner_change_note',
        'owner_changed_at',
        'owner_changed_by',
        'owner_change_count',
    ];

    protected function casts(): array
    {
        return [
            'is_settled' => 'boolean',
            'loaded_weight' => 'decimal:2',
            'loaded_on' => 'date',
            'owner_changed_at' => 'datetime',
            'owner_change_count' => 'integer',
        ];
    }

    public function cargoRequest()
    {
        return $this->belongsTo(CargoRequest::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function previousOwner()
    {
        return $this->belongsTo(Owner::class, 'previous_owner_id');
    }

    public function ownerChangedBy()
    {
        return $this->belongsTo(User::class, 'owner_changed_by');
    }

    public function ownerLogs()
    {
        return $this->hasMany(WagonOwnerLog::class)->latest('id');
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

    /** آیا صاحب کالای این واگن ویرایش شده است؟ */
    public function isOwnerEdited(): bool
    {
        return (int) $this->owner_change_count > 0 || $this->owner_changed_at !== null;
    }

    /** شناسه صاحب کالای مؤثر واگن (با بازگشت به صاحب کالای درخواست بارگیری) */
    public function effectiveOwnerId(): ?int
    {
        return $this->owner_id ?? $this->cargoRequest?->owner_id;
    }

    /** نام صاحب کالای فعلی واگن */
    public function ownerName(): string
    {
        return $this->owner?->name ?? $this->cargoRequest?->owner?->name ?? '—';
    }

    public function scopePendingSettlement($query)
    {
        return $query->where('is_settled', false);
    }

    public function scopeLoaded($query)
    {
        return $query->whereNotNull('loaded_weight');
    }

    public function scopeOwnerEdited($query)
    {
        return $query->where(function ($q) {
            $q->where('owner_change_count', '>', 0)
                ->orWhereNotNull('owner_changed_at');
        });
    }
}
