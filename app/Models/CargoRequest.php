<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;

class CargoRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_LOADED = 'loaded';
    public const STATUS_BILLED = 'billed';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'در انتظار بارگیری',
        self::STATUS_LOADED => 'بارگیری شده',
        self::STATUS_BILLED => 'صورتحساب شده',
    ];

    protected $fillable = [
        'request_number', 'owner_id', 'representative_id', 'operator_id',
        'request_date', 'request_date_shamsi', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['request_date' => 'date'];
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

    public function wagons()
    {
        return $this->hasMany(Wagon::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /** آیا صاحب کالای این درخواست مشخص شده است؟ */
    public function hasKnownOwner(): bool
    {
        return $this->owner_id !== null;
    }

    /** نام صاحب کالا (یا «ناشناس» در صورت عدم تعیین) */
    public function ownerName(): string
    {
        return $this->owner?->name ?? 'ناشناس';
    }

    public static function generateRequestNumber(): string
    {
        return DocumentNumber::next(self::class, 'request_number', 'CR');
    }

    /** همه واگن‌های این درخواست وزن بارگیری ثبت‌شده دارند؟ */
    public function allWagonsLoaded(): bool
    {
        return $this->wagons->isNotEmpty()
            && $this->wagons->every(fn (Wagon $wagon) => $wagon->loaded_weight !== null);
    }
}
