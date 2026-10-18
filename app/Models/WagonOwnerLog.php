<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WagonOwnerLog extends Model
{
    protected $fillable = [
        'wagon_id',
        'billing_id',
        'old_owner_id',
        'new_owner_id',
        'old_owner_name',
        'new_owner_name',
        'user_id',
        'note',
    ];

    public function wagon()
    {
        return $this->belongsTo(Wagon::class);
    }

    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }

    public function oldOwner()
    {
        return $this->belongsTo(Owner::class, 'old_owner_id');
    }

    public function newOwner()
    {
        return $this->belongsTo(Owner::class, 'new_owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
