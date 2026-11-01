<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class, 'categoryId');
    }
}
