<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MyRecipeController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RecipeController::class, 'home'])->name('home');
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');

Route::middleware('auth')->group(function () {
    Route::put('/recipes/{recipe}/rating', [RatingController::class, 'update'])->whereNumber('recipe')->name('recipes.rating.update');
    Route::delete('/recipes/{recipe}/rating', [RatingController::class, 'destroy'])->whereNumber('recipe')->name('recipes.rating.destroy');
    Route::post('/recipes/{recipe}/comments', [CommentController::class, 'store'])->whereNumber('recipe')->name('recipes.comments.store');
    Route::put('/recipes/{recipe}/comments/{comment}', [CommentController::class, 'update'])->whereNumber(['recipe', 'comment'])->name('recipes.comments.update');
    Route::delete('/recipes/{recipe}/comments/{comment}', [CommentController::class, 'destroy'])->whereNumber(['recipe', 'comment'])->name('recipes.comments.destroy');
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->whereNumber('recipe')->name('recipes.edit');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->whereNumber('recipe')->name('recipes.update');
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->whereNumber('recipe')->name('recipes.destroy');
    Route::get('/my/recipes', [MyRecipeController::class, 'index'])->name('my.recipes.index');
});
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->whereNumber('recipe')->name('recipes.show');

Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/register', 'showRegister')->name('register');
    Route::post('/register', 'register')->name('register.store');
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
