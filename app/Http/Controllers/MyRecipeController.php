<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyRecipeController extends Controller
{
    public function index(Request $request): View
    {
        $recipes = $request->user()->recipes()->with(['user', 'category', 'tags'])
            ->withAvg('ratings', 'score')->withCount('ratings')
            ->orderByDesc('createdAt')->orderByDesc('id')
            ->paginate(12)->withQueryString();

        return view('my.recipes', [
            'recipes' => $recipes,
            'hasRecipes' => $recipes->total() > 0 || $request->user()->recipes()->exists(),
        ]);
    }
}
