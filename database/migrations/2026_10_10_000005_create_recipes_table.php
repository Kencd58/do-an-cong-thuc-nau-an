<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adopt an existing SQL-created table without rebuilding or changing its data.
        if (Schema::hasTable('recipes')) {
            return;
        }

        Schema::create('recipes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('userId')->constrained('users', indexName: 'recipes_userId_foreign')->restrictOnDelete();
            $table->foreignId('categoryId')->constrained('categories', indexName: 'recipes_categoryId_foreign')->restrictOnDelete();
            $table->string('title', 150);
            $table->text('description');
            $table->unsignedSmallInteger('prepTime');
            $table->unsignedSmallInteger('cookTime');
            $table->unsignedSmallInteger('servings');
            $table->string('difficulty', 10);
            $table->string('imageUrl', 1024)->nullable();
            $table->string('imageId', 255)->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->index('userId', 'recipes_userId_index');
            $table->index('categoryId', 'recipes_categoryId_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
