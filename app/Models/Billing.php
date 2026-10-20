<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Billing extends Model
{
    use SoftDeletes;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_ISSUED => 'صادر شده',
        self::STATUS_CANCELLED => 'لغو شده',
    ];

    protected $fillable = [
        'billing_number', 'owner_id', 'representative_id', 'operator_id',
        'owner_name', 'representative_name',
        'billing_date', 'billing_date_shamsi', 'base_amount', 'tax_percentage',
        'tax_amount', 'total_amount', 'receipt_number', 'receipt_date_shamsi',
        'bank_name', 'receipt_amount', 'settled_at',
        'status', 'notes', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'billing_date' => 'date',
            'cancelled_at' => 'datetime',
            'settled_at' => 'datetime',
            'base_amount' => 'integer',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'receipt_amount' => 'integer',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function representative()
    {
        return $this->belongsTo(Representative::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function items()
    {
        return $this->hasMany(BillingItem::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isSettled(): bool
    {
        return ! $this->isCancelled()
            && ($this->settled_at !== null || trim((string) $this->receipt_number) !== '');
    }

    public function statusLabel(): string
    {
        if ($this->isCancelled()) {
            return 'لغو شده';
        }

        if ($this->isSettled()) {
            return 'تسویه شده';
        }

        return 'در انتظار ثبت فیش واریزی';
    }

    /** نام صاحب کالا در لحظه صدور (snapshot) با بازگشت به رابطه در رکوردهای قدیمی */
    public function ownerName(): string
    {
        return $this->owner_name ?: ($this->owner?->name ?? '—');
    }

    public function representativeName(): ?string
    {
        return $this->representative_name ?: $this->representative?->name;
    }

    public static function generateBillingNumber(): string
    {
        return DocumentNumber::next(self::class, 'billing_number', 'INV');
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('billing_date', [$from, $to]);
    }
}
