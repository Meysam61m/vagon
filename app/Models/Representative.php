<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Representative extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'national_id', 'phone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function owners()
    {
        return $this->belongsToMany(Owner::class, 'owner_representative');
    }

    public function cargoRequests()
    {
        return $this->hasMany(CargoRequest::class);
    }

    public function billings()
    {
        return $this->hasMany(Billing::class);
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
                ->orWhere('phone', 'like', $term);
        });
    }
}
