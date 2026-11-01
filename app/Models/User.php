<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    protected $rememberTokenName = 'rememberToken';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'rememberToken'];

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'userId');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'userId');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'userId');
    }
}
