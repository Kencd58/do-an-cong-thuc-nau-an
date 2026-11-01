<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->transactionStarted = true;
        // Clean only this transaction's demo fixture; tearDown restores existing test data.
        $users = User::whereIn('email', ['demo.a@example.test', 'demo.b@example.test'])->pluck('id');
        DB::table('recipes')->whereIn('userId', $users)->delete();
        DB::table('ratings')->whereIn('userId', $users)->delete();
        DB::table('comments')->whereIn('userId', $users)->delete();
        DB::table('users')->whereIn('id', $users)->delete();
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    public function test_tc01_default_seeder_only_seeds_repeatable_catalogs(): void
    {
        $users = User::count();
        $recipes = Recipe::count();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        foreach (['categories' => 5, 'tags' => 10, 'ingredients' => 30] as $table => $count) {
            $this->assertSame($count, DB::table($table)->count());
        }
        $this->assertSame($users, User::count());
        $this->assertSame($recipes, Recipe::count());
    }

    public function test_tc19_demo_is_complete_repeatable_and_leaves_other_accounts_unchanged(): void
    {
        $other = User::create(['email' => 'other-'.bin2hex(random_bytes(6)).'@example.test', 'name' => 'Other', 'password' => 'unchanged']);
        $before = $other->fresh()->getAttributes();
        $this->seed(DemoSeeder::class);
        $users = User::whereIn('email', ['demo.a@example.test', 'demo.b@example.test'])->orderBy('email')->get();
        $this->assertCount(2, $users);
        foreach ($users as $index => $user) {
            $this->assertSame('Demo '.['A', 'B'][$index], $user->name);
            $this->assertTrue(Hash::check('Demo12345!', $user->password));
            $this->assertSame(15, $user->recipes()->count());
        }
        $recipes = Recipe::whereIn('userId', $users->modelKeys())->with(['ingredients', 'steps', 'tags', 'ratings', 'comments'])->orderBy('title')->get();
        $this->assertCount(30, $recipes);
        $this->assertCount(5, $recipes->pluck('categoryId')->unique());
        $this->assertCount(3, $recipes->pluck('difficulty')->unique());
        $this->assertGreaterThan(5, $recipes->pluck('cookTime')->unique()->count());
        foreach ($recipes as $index => $recipe) {
            $this->assertSame(sprintf('Demo Recipe %02d', $index + 1), $recipe->title);
            $this->assertSame($users[$index < 15 ? 0 : 1]->id, $recipe->userId);
            $this->assertNotEmpty($recipe->description);
            $this->assertGreaterThan(0, $recipe->prepTime + $recipe->cookTime);
            $this->assertNull($recipe->imageUrl);
            $this->assertNull($recipe->imageId);
            $this->assertCount(2, $recipe->ingredients);
            $this->assertCount(2, $recipe->steps);
            $this->assertCount(2, $recipe->tags);
            $this->assertCount(1, $recipe->ratings);
            $this->assertNotEquals($recipe->userId, $recipe->ratings[0]->userId);
            $this->assertSame(1 + $index % 5, $recipe->ratings[0]->score);
            $this->get(route('recipes.show', $recipe))->assertOk();
        }
        $this->assertCount(12, $recipes[0]->comments);
        $this->assertSame(15, $recipes->sum(fn ($recipe) => $recipe->comments->count()));
        $snapshot = $this->snapshot();
        $this->seed(DemoSeeder::class);
        $this->assertSame($snapshot, $this->snapshot());
        $this->assertSame($before, $other->fresh()->getAttributes());
    }

    public function test_tc19_production_guard_prevents_all_writes(): void
    {
        $before = $this->snapshot();
        $this->app->instance('env', 'production');
        try {
            app(DemoSeeder::class)->run();
            $this->fail('Production must reject demo data.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
            $this->assertSame($before, $this->snapshot());
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_tc19_conflicting_demo_email_is_preserved_and_rolls_back(): void
    {
        User::create(['email' => 'demo.b@example.test', 'name' => 'Existing person', 'password' => 'unchanged']);
        $before = $this->snapshot();
        try {
            app(DemoSeeder::class)->run();
            $this->fail('An existing account must not be taken over.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already in use', $exception->getMessage());
            $this->assertSame($before, $this->snapshot());
        }
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['users', 'recipes', 'recipe_ingredients', 'recipe_steps', 'recipe_tag', 'ratings', 'comments'] as $table) {
            $query = DB::table($table);
            $table === 'recipe_tag' ? $query->orderBy('recipeId')->orderBy('tagId') : $query->orderBy('id');
            $snapshot[$table] = $query->get()->toJson();
        }

        return $snapshot;
    }
}
