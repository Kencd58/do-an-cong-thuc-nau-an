<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adopt an existing SQL-created table without rebuilding or changing its data.
        if (Schema::hasTable('recipe_ingredients')) {
            return;
        }

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('recipeId')->constrained('recipes', indexName: 'recipe_ingredients_recipeId_foreign')->cascadeOnDelete();
            $table->foreignId('ingredientId')->constrained('ingredients', indexName: 'recipe_ingredients_ingredientId_foreign')->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->string('unit', 20);
            $table->string('note', 255)->nullable();
            $table->unique(['recipeId', 'ingredientId'], 'recipe_ingredients_recipeId_ingredientId_unique');
            $table->index('ingredientId', 'recipe_ingredients_ingredientId_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};
