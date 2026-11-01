<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class, 'ingredientId');
    }
}
