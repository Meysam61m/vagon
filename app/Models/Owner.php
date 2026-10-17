<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Owner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'national_id', 'economic_code', 'phone', 'address', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function representatives()
    {
        return $this->belongsToMany(Representative::class, 'owner_representative');
    }

    public function cargoRequests()
    {
        return $this->hasMany(CargoRequest::class);
    }

    public function billings()
    {
        return $this->hasMany(Billing::class);
    }

    public function wagons()
    {
        return $this->hasManyThrough(Wagon::class, CargoRequest::class);
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
            $q->where('name', 'like', $term)
                ->orWhere('national_id', 'like', $term)
                ->orWhere('phone', 'like', $term)
                ->orWhere('economic_code', 'like', $term);
        });
    }
}
