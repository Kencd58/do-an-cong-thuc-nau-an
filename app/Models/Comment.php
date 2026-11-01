<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    protected $fillable = ['recipeId', 'userId', 'content'];

    protected $casts = ['recipeId' => 'integer', 'userId' => 'integer'];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipeId');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
