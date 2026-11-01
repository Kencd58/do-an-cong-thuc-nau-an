<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRecipeRequest;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Tag;
use Illuminate\View\View;

class MyRecipeController extends Controller
{
    public function index(ListRecipeRequest $request): View
    {
        $filters = $request->validated();
        $recipes = $request->user()->recipes()->filter($filters)->with(['user', 'category', 'tags'])
            ->paginate(12)->withQueryString();

        return view('my.recipes', [
            'recipes' => $recipes, 'filters' => $filters,
            'hasRecipes' => $recipes->total() > 0 || $request->user()->recipes()->exists(),
            'categories' => Category::orderBy('name')->get(), 'tags' => Tag::orderBy('name')->get(),
            'ingredients' => Ingredient::orderBy('name')->get(), 'difficulties' => config('recipe.difficulties'),
        ]);
    }
}
