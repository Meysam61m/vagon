<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_OPERATOR = 'operator';

    public const ROLE_LABELS = [
        self::ROLE_ADMIN => 'ادمین',
        self::ROLE_OPERATOR => 'اپراتور',
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    public function cargoRequests()
    {
        return $this->hasMany(CargoRequest::class, 'operator_id');
    }

    public function billings()
    {
        return $this->hasMany(Billing::class, 'operator_id');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.trim($term).'%';

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone', 'like', $term);
        });
    }
}
