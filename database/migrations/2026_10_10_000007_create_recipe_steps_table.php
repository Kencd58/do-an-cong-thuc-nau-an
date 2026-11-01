<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adopt an existing SQL-created table without rebuilding or changing its data.
        if (Schema::hasTable('recipe_steps')) {
            return;
        }

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('recipeId')->constrained('recipes', indexName: 'recipe_steps_recipeId_foreign')->cascadeOnDelete();
            $table->unsignedSmallInteger('stepOrder');
            $table->text('instruction');
            $table->unique(['recipeId', 'stepOrder'], 'recipe_steps_recipeId_stepOrder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_steps');
    }
};
