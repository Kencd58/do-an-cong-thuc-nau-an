<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\SampleRecipeSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SampleRecipeSeederTest extends TestCase
{
    public function test_ten_complete_recipes_are_repeatable_and_visible_on_home(): void
    {
        DB::beginTransaction();
        try {
            $emails = ['sample.minh@example.test', 'sample.an@example.test'];
            User::whereIn('email', $emails)->delete();
            $before = Recipe::count();
            $this->seed(SampleRecipeSeeder::class);
            $users = User::whereIn('email', $emails)->get();
            $this->assertCount(2, $users);
            foreach ($users as $user) {
                $this->assertTrue(Hash::check('Sample12345!', $user->password));
                $this->assertSame(5, $user->recipes()->count());
            }
            $recipes = Recipe::whereIn('userId', $users->modelKeys())->with(['ingredients', 'steps', 'tags'])->get();
            $this->assertCount(10, $recipes);
            $this->assertSame($before + 10, Recipe::count());
            $this->assertCount(5, $recipes->pluck('categoryId')->unique());
            foreach ($recipes as $recipe) {
                $this->assertGreaterThanOrEqual(4, $recipe->ingredients->count());
                $this->assertGreaterThanOrEqual(3, $recipe->steps->count());
                $this->assertGreaterThanOrEqual(2, $recipe->tags->count());
                $this->get(route('recipes.show', $recipe))->assertOk()->assertSee($recipe->title);
            }
            $this->get('/')->assertOk()->assertSee('Nước chanh tươi')->assertSee('Bánh flan caramel');
            $recipes->first()->update(['description' => 'Nội dung đã chỉnh khi test']);
            $snapshot = [];
            foreach (['users', 'recipes', 'recipe_ingredients', 'recipe_steps', 'recipe_tag'] as $table) {
                $snapshot[$table] = DB::table($table)->get()->toJson();
            }
            $this->seed(SampleRecipeSeeder::class);
            foreach ($snapshot as $table => $data) {
                $this->assertSame($data, DB::table($table)->get()->toJson());
            }
        } finally {
            DB::rollBack();
        }
    }
}
