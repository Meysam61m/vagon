<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WagonOwnerLog extends Model
{
    protected $fillable = [
        'wagon_id',
        'billing_id',
        'change_type',
        'old_wagon_number',
        'new_wagon_number',
        'old_owner_id',
        'new_owner_id',
        'old_owner_name',
        'new_owner_name',
        'user_id',
        'note',
    ];

    public function wagon(): BelongsTo
    {
        return $this->belongsTo(Wagon::class);
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    public function oldOwner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'old_owner_id');
    }

    public function newOwner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'new_owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isWagonNumberChange(): bool
    {
        if (in_array($this->change_type, ['wagon_number', 'both'], true)) {
            return true;
        }

        return $this->old_wagon_number !== null
            && $this->new_wagon_number !== null
            && (string) $this->old_wagon_number !== (string) $this->new_wagon_number;
    }

    public function isOwnerChange(): bool
    {
        if (in_array($this->change_type, ['owner', 'both'], true)) {
            return (int) $this->old_owner_id !== (int) $this->new_owner_id || $this->change_type === 'owner' && ! $this->isWagonNumberChange();
        }

        return (int) $this->old_owner_id !== (int) $this->new_owner_id;
    }

    public function changeTypeLabel(): string
    {
        return match ($this->change_type) {
            'wagon_number' => 'اصلاح شماره واگن',
            'both' => 'اصلاح شماره و صاحب کالا',
            default => 'تغییر صاحب کالا',
        };
    }
}
