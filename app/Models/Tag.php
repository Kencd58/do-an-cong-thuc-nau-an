<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_tag', 'tagId', 'recipeId');
    }
}
