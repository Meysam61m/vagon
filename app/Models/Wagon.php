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
        'previous_wagon_number',
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

    /** آیا این واگن (شماره واگن یا صاحب کالا) ویرایش شده است؟ */
    public function isOwnerEdited(): bool
    {
        return (int) $this->owner_change_count > 0
            || $this->owner_changed_at !== null
            || $this->isNumberEdited();
    }

    /** آیا شماره این واگن پس از ثبت اولیه اصلاح شده است؟ */
    public function isNumberEdited(): bool
    {
        return $this->previous_wagon_number !== null
            && $this->previous_wagon_number !== ''
            && (string) $this->previous_wagon_number !== (string) $this->wagon_number;
    }

    /** آیا صاحب کالای این واگن تغییر یافته است؟ */
    public function isOwnerChanged(): bool
    {
        if ($this->previous_owner_id !== null && (int) $this->previous_owner_id !== (int) $this->effectiveOwnerId()) {
            return true;
        }

        return ((int) $this->owner_change_count > 0 || $this->owner_changed_at !== null)
            && ! $this->isNumberEdited();
    }

    /** عنوان فارسی نوع ویرایش انجام‌شده روی واگن */
    public function editTypeLabel(): string
    {
        $numEdited = $this->isNumberEdited();
        $ownerEdited = $this->previous_owner_id !== null
            && (int) $this->previous_owner_id !== (int) $this->effectiveOwnerId();

        if ($numEdited && $ownerEdited) {
            return 'اصلاح شماره و صاحب کالا';
        }

        if ($numEdited) {
            return 'اصلاح شماره واگن';
        }

        return 'تغییر صاحب کالا';
    }

    /** شناسه صاحب کالای مؤثر واگن (با بازگشت به صاحب کالای درخواست بارگیری) */
    public function effectiveOwnerId(): ?int
    {
        return $this->owner_id ?? $this->cargoRequest?->owner_id;
    }

    /** آیا صاحب کالای این واگن مشخص شده است؟ */
    public function hasKnownOwner(): bool
    {
        return $this->effectiveOwnerId() !== null;
    }

    /** نام صاحب کالای فعلی واگن */
    public function ownerName(): string
    {
        return $this->owner?->name ?? $this->cargoRequest?->owner?->name ?? 'ناشناس';
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
                ->orWhereNotNull('owner_changed_at')
                ->orWhere(function ($sub) {
                    $sub->whereNotNull('previous_wagon_number')
                        ->whereColumn('previous_wagon_number', '!=', 'wagon_number');
                });
        });
    }

    public function scopeNumberEdited($query)
    {
        return $query->whereNotNull('previous_wagon_number')
            ->whereColumn('previous_wagon_number', '!=', 'wagon_number');
    }

    public function scopeOnlyOwnerChanged($query)
    {
        return $query->whereNotNull('previous_owner_id')
            ->whereColumn('previous_owner_id', '!=', 'owner_id');
    }
}
