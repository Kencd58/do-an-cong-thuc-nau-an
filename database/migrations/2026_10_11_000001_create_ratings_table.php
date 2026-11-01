<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipeId')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('userId')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->unique(['recipeId', 'userId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
