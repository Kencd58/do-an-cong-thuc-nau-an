<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeStep extends Model
{
    public $timestamps = false;

    protected $fillable = ['stepOrder', 'instruction'];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipeId');
    }
}
