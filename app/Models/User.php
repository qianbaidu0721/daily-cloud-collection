<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'openid',
        'unionid',
        'nickname',
        'avatar',
        'total_days',
    ];

    protected function casts(): array
    {
        return [
            'total_days' => 'integer',
        ];
    }

    protected $hidden = [
        'unionid',
        'remember_token',
    ];

    public function clouds(): HasMany
    {
        return $this->hasMany(Cloud::class);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
