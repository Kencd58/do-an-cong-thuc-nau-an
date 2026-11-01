<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adopt an existing SQL-created table without rebuilding or changing its data.
        if (Schema::hasTable('recipe_tag')) {
            return;
        }

        Schema::create('recipe_tag', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->foreignId('recipeId')->constrained('recipes', indexName: 'recipe_tag_recipeId_foreign')->cascadeOnDelete();
            $table->foreignId('tagId')->constrained('tags', indexName: 'recipe_tag_tagId_foreign')->restrictOnDelete();
            $table->primary(['recipeId', 'tagId']);
            $table->index('tagId', 'recipe_tag_tagId_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_tag');
    }
};
