<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipeId')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('userId')->constrained('users')->restrictOnDelete();
            $table->text('content');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->index(['recipeId', 'createdAt', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
