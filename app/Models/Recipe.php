<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    public const CREATED_AT = 'createdAt';

    public const UPDATED_AT = 'updatedAt';

    protected $fillable = ['userId', 'title', 'description', 'categoryId', 'prepTime', 'cookTime', 'servings', 'difficulty', 'imageUrl', 'imageId'];

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query->withAvg('ratings', 'score')->withCount('ratings');
        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $keyword = strtr($filters['keyword'], ['!' => '!!', '%' => '!%', '_' => '!_']);
            $query->whereRaw("title LIKE ? ESCAPE '!'", ['%'.$keyword.'%']);
        }
        foreach (['categoryId', 'difficulty'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['tagIds'])) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereIn('tags.id', $filters['tagIds']));
        }
        if (! empty($filters['ingredientIds'])) {
            $query->whereHas('ingredients', fn (Builder $ingredients) => $ingredients->whereIn('ingredientId', $filters['ingredientIds']));
        }
        if (isset($filters['maxTotalTime']) && $filters['maxTotalTime'] !== '') {
            $query->whereRaw('(prepTime + cookTime) <= ?', [$filters['maxTotalTime']]);
        }

        $direction = ($filters['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        return match ($filters['sort'] ?? 'time') {
            'rating' => $query->orderByRaw("COALESCE(ratings_avg_score, 0) {$direction}")->orderBy('id'),
            'difficulty' => $query->orderByRaw("CASE difficulty WHEN 'easy' THEN 1 WHEN 'medium' THEN 2 WHEN 'hard' THEN 3 ELSE 4 END {$direction}")->orderBy('id'),
            default => $query->orderByRaw("(prepTime + cookTime) {$direction}")->orderBy('id'),
        };
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'recipeId');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'recipeId');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoryId');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class, 'recipeId')->orderBy('id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class, 'recipeId')->orderBy('stepOrder');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'recipe_tag', 'recipeId', 'tagId');
    }
}
