<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecipeListTest extends TestCase
{
    private User $owner;

    private User $other;

    private array $items = [];

    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('recipe_test', DB::selectOne('SELECT DATABASE() AS db')->db);
        DB::beginTransaction();
        $this->transactionStarted = true;
        // Isolate expectations from previous browser fixtures; rollback restores them.
        Recipe::query()->delete();
        $this->owner = User::create(['name' => 'Bếp A', 'email' => uniqid('list-a-').'@example.test', 'password' => 'unused']);
        $this->other = User::create(['name' => 'Bếp B', 'email' => uniqid('list-b-').'@example.test', 'password' => 'unused']);
        $this->items[] = $this->recipe('Trứng 100% ngon', 1, 'easy', 15, [1, 2], [7, 11]);
        $this->items[] = $this->recipe('Canh_cải', 2, 'medium', 30, [2], [18]);
        $this->items[] = $this->recipe('Trứng hấp', 1, 'hard', 45, [3], [7], $this->other);
        for ($i = 0; $i < 12; $i++) {
            $this->items[] = $this->recipe('Món thường '.sprintf('%02d', $i), 3, 'easy', 60, [4], [8]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function recipe(string $title, int $category, string $difficulty, int $time, array $tags, array $ingredients, ?User $user = null): Recipe
    {
        $recipe = new Recipe(['userId' => ($user ?? $this->owner)->id, 'title' => $title,
            'description' => 'Mô tả có từ khóa bí mật', 'categoryId' => $category, 'difficulty' => $difficulty,
            'prepTime' => 5, 'cookTime' => $time - 5, 'servings' => 2]);
        $recipe->createdAt = '2025-01-01 00:00:00';
        $recipe->save();
        $recipe->tags()->sync($tags);
        foreach ($ingredients as $id) {
            $recipe->ingredients()->create(['ingredientId' => $id, 'quantity' => 1, 'unit' => 'g']);
        }

        return $recipe;
    }

    private function listing(array $query = [], string $path = '/recipes')
    {
        return $this->get($path.'?'.http_build_query($query))->assertOk();
    }

    private function assertItems(array $query, array $items, string $path = '/recipes'): void
    {
        $this->listing($query, $path)->assertViewHas('recipes', function ($recipes) use ($items) {
            $this->assertSame(array_map(fn ($item) => $item->id, $items), $recipes->pluck('id')->all());
            $this->assertSame(count($items), $recipes->total());

            return true;
        });
    }

    public function test_list01_home_is_newest_and_search_defaults_to_fastest_twelve_per_page(): void
    {
        $this->listing([], '/')->assertViewHas('recipes', function ($recipes) {
            $this->assertSame(array_map(fn ($item) => $item->id, array_reverse(array_slice($this->items, 3))), $recipes->pluck('id')->all());
            return true;
        });
        $this->listing()->assertViewHas('recipes', function ($recipes) {
            $this->assertSame(15, $recipes->total());
            $this->assertSame(12, $recipes->count());
            $this->assertSame(12, $recipes->perPage());
            $this->assertSame([$this->items[0]->id, $this->items[1]->id, $this->items[2]->id], $recipes->pluck('id')->take(3)->all());
            $this->assertTrue($recipes->every(fn ($recipe) => $recipe->relationLoaded('user') && $recipe->relationLoaded('category') && $recipe->relationLoaded('tags')));
            return true;
        });
        $this->listing()->assertViewHas('filters', ['tagIds' => [], 'ingredientIds' => [], 'sort' => 'time', 'order' => 'asc', 'page' => 1]);
        $this->listing(['page' => 2])->assertViewHas('recipes', fn ($recipes) => $recipes->count() === 3);
        $this->listing(['keyword' => '  ', 'categoryId' => '', 'difficulty' => '', 'maxTotalTime' => '', 'tagIds' => '', 'ingredientIds' => '', 'sort' => '', 'order' => '', 'page' => ''])
            ->assertViewHas('recipes', fn ($recipes) => $recipes->total() === 15);
    }

    public function test_list02_keyword_only_searches_title_and_treats_wildcards_literally(): void
    {
        $this->assertItems(['keyword' => '  Trứng  ', 'sort' => 'time'], [$this->items[0], $this->items[2]]);
        $this->assertItems(['keyword' => '%'], [$this->items[0]]);
        $this->assertItems(['keyword' => '_'], [$this->items[1]]);
        $this->assertItems(['keyword' => 'bí mật'], []);
        $special = $this->recipe('Sốt ! \\ 0', 1, 'easy', 5, [], []);
        foreach (['!', '\\', '0'] as $keyword) {
            $query = ['keyword' => $keyword];
            if ($keyword === '0') {
                $query['maxTotalTime'] = 5;
            }
            $this->assertItems($query, [$special]);
        }
        $this->assertItems(['keyword' => "' OR 1=1 --"], []);
    }

    public function test_list03_individual_filters_and_and_between_groups(): void
    {
        $this->assertItems(['categoryId' => 2], [$this->items[1]]);
        $this->assertItems(['difficulty' => 'hard'], [$this->items[2]]);
        $this->assertItems(['maxTotalTime' => 15], [$this->items[0]]);
        $this->assertItems(['maxTotalTime' => 14], []);
        $this->assertItems(['tagIds' => [3]], [$this->items[2]]);
        $this->assertItems(['ingredientIds' => [18]], [$this->items[1]]);
        $combined = ['keyword' => 'Trứng', 'categoryId' => 1, 'tagIds' => [1, 3],
            'ingredientIds' => [7, 18], 'difficulty' => 'easy', 'maxTotalTime' => 15, 'sort' => 'time'];
        $this->assertItems($combined, [$this->items[0]]);
        $this->assertItems(array_replace($combined, ['ingredientIds' => [18]]), []);
    }

    public function test_list04_or_inside_groups_without_duplicate_recipes(): void
    {
        $this->assertItems(['tagIds' => [1, 2], 'sort' => 'time'], [$this->items[0], $this->items[1]]);
        $this->assertItems(['ingredientIds' => [7, 11, 18], 'sort' => 'time'], array_slice($this->items, 0, 3));
        $this->assertItems(['tagIds' => [1, 2], 'ingredientIds' => [7, 11, 18], 'sort' => 'time'], [$this->items[0], $this->items[1]]);
    }

    public function test_list05_all_sorts_have_stable_id_tie_breakers(): void
    {
        $a = $this->recipe('Sort Z', 1, 'easy', 10, [], []);
        $b = $this->recipe('Sort A', 1, 'medium', 20, [], []);
        $c = $this->recipe('Sort C', 1, 'hard', 10, [], []);
        Rating::create(['recipeId' => $a->id, 'userId' => $this->owner->id, 'score' => 4]);
        Rating::create(['recipeId' => $b->id, 'userId' => $this->owner->id, 'score' => 2]);
        Rating::create(['recipeId' => $c->id, 'userId' => $this->owner->id, 'score' => 4]);
        $this->assertItems(['keyword' => 'Sort', 'sort' => 'time'], [$a, $c, $b]);
        $this->assertItems(['keyword' => 'Sort', 'sort' => 'time', 'order' => 'desc'], [$b, $a, $c]);
        $this->assertItems(['keyword' => 'Sort', 'sort' => 'rating', 'order' => 'desc'], [$a, $c, $b]);
        $this->assertItems(['keyword' => 'Sort', 'sort' => 'difficulty'], [$a, $b, $c]);
        $this->assertItems(['keyword' => 'Sort', 'sort' => 'difficulty', 'order' => 'desc'], [$c, $b, $a]);
    }

    public function test_list06_pagination_keeps_query_and_filter_form_resets_page(): void
    {
        $query = ['keyword' => 'Món', 'categoryId' => 3, 'tagIds' => [4, 5], 'ingredientIds' => [8, 9],
            'difficulty' => 'easy', 'maxTotalTime' => 60, 'sort' => 'difficulty', 'order' => 'desc'];
        $this->recipe('Món thường 12', 3, 'easy', 60, [4], [8]);
        $this->actingAs($this->owner);
        $this->listing($query + ['page' => 2])->assertDontSee('name="page"', false)
                ->assertSee('method="GET" action="'.url('/recipes').'"', false)
                ->assertViewHas('recipes', function ($recipes) use ($query) {
                    parse_str(parse_url($recipes->url(1), PHP_URL_QUERY), $actual);
                    $this->assertEquals($query + ['page' => 1], $actual);
                    $this->assertSame(1, $recipes->count());

                    return true;
                })->assertViewHas('filters', $query + ['page' => '2']);
        $this->listing($query)->assertViewHas('recipes', fn ($recipes) => $recipes->currentPage() === 1);
        $this->listing(['page' => 2], '/my/recipes')->assertDontSee('Bộ lọc')
            ->assertViewHas('recipes', fn ($recipes) => $recipes->currentPage() === 2);
    }

    public function test_list07_personal_list_cannot_be_overridden_by_client_owner(): void
    {
        $this->get('/my/recipes?userId='.$this->owner->id)->assertRedirect('/login');
        $this->actingAs($this->other);
        $this->listing(['userId' => $this->owner->id, 'tagIds' => [1, 3]], '/my/recipes')
            ->assertViewHas('recipes', fn ($recipes) => $recipes->total() === 1 && $recipes->first()->is($this->items[2]))
            ->assertDontSee('Bộ lọc');
    }

    public function test_list08_invalid_queries_redirect_cleanly_before_recipe_query(): void
    {
        $invalid = [
            ['categoryId' => 999999], ['categoryId' => [1]], ['tagIds' => [999999]], ['ingredientIds' => [999999]],
            ['sort' => 'title desc; DROP TABLE recipes'], ['order' => 'sideways'], ['page' => -1], ['page' => 0], ['page' => '1.5'],
            ['keyword' => str_repeat('a', 101)], ['keyword' => ['x']], ['difficulty' => 'impossible'],
            ['maxTotalTime' => 0], ['maxTotalTime' => 2881], ['maxTotalTime' => '1.5'],
            ['tagIds' => '1'], ['tagIds' => [1, 1]], ['tagIds' => range(1, 11)],
            ['ingredientIds' => '7'], ['ingredientIds' => [7, 7]], ['ingredientIds' => range(1, 31)],
            ['tagIds' => [[1]]], ['ingredientIds' => ['bad']], ['sort' => ['time']], ['page' => [1]],
        ];
        $this->actingAs($this->owner);
        foreach (['/recipes'] as $path) {
            foreach ($invalid as $query) {
                DB::enableQueryLog();
                DB::flushQueryLog();
                $this->from($path.'?sort=invalid')->get($path.'?'.http_build_query($query))
                    ->assertRedirect($path)->assertSessionHas('errors', fn ($errors) => collect($errors->getBag('filters')->keys())
                    ->contains(fn ($key) => str_starts_with($key, array_key_first($query))));
                $queries = DB::getQueryLog();
                DB::disableQueryLog();
                $this->assertFalse(collect($queries)->contains(fn ($entry) => str_contains($entry['query'], 'from `recipes`')));
                $this->get($path)->assertOk()->assertSee('Bộ lọc chưa hợp lệ')->assertDontSee('validation.min.numeric');
                $this->get($path)->assertOk()->assertDontSee('Bộ lọc chưa hợp lệ');
            }
        }
    }

    public function test_list09_empty_and_out_of_range_states(): void
    {
        $this->listing(['keyword' => 'không tồn tại'])->assertSee('Không tìm thấy công thức phù hợp')->assertSee('Xóa bộ lọc');
        $this->listing(['categoryId' => 1, 'page' => 999])->assertSee('Trang này không có công thức')
            ->assertSee('Về trang 1')->assertViewHas('recipes', fn ($recipes) => str_contains($recipes->url(1), 'categoryId=1'));
        $this->actingAs($this->other);
        $this->listing(['keyword' => 'không tồn tại'], '/my/recipes')->assertSee($this->items[2]->title)->assertDontSee('Bộ lọc');
        $this->listing(['page' => 2], '/my/recipes')->assertSee('Về trang 1');
        $this->items[2]->delete();
        $this->listing([], '/my/recipes')->assertSee('Đăng công thức đầu tiên')->assertDontSee('Không tìm thấy công thức phù hợp');
    }

    public function test_list10_cards_and_existing_show_menu_routes_remain_available(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->listing(['keyword' => '100%'])->assertSee('Trứng 100% ngon')->assertSee('Bếp A')->assertSee('Món chính')
            ->assertSee('15 phút')->assertSee('Dễ')->assertSee('Nhanh')->assertSee('Dễ làm')->assertSee('recipe-default.png');
        $this->actingAs($this->owner)->get('/recipes')->assertOk()->assertSee('Công thức của tôi')->assertSee('Tạo công thức');
        $this->get('/my/recipes')->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        DB::disableQueryLog();
        $this->assertStringContainsString('`ratings`', $queries);
        $this->assertStringNotContainsString('`comments`', $queries);
        $this->get('/recipes/'.$this->items[0]->id)->assertOk()->assertSee('Trứng 100% ngon');
        $this->get('/recipes/create')->assertOk();
        $this->get('/recipes/'.$this->items[0]->id.'/edit')->assertOk();
    }

    public function test_home_shows_community_recipes_without_search_filters(): void
    {
        $latest = $this->recipe('Món mới từ bếp khác', 1, 'easy', 20, [], [], $this->other);
        $latest->update(['imageUrl' => 'https://example.test/recipe.jpg']);
        $this->actingAs($this->owner)->get('/?keyword=missing&sort=title&userId='.$this->owner->id)
            ->assertOk()->assertViewIs('recipes.home')->assertSee('Công thức mới chia sẻ')
            ->assertSee('Món mới từ bếp khác')->assertSee('Bếp B')
            ->assertSee('recipe-default.png')->assertDontSee('https://example.test/recipe.jpg')->assertDontSee('name="keyword"', false)
            ->assertViewHas('recipes', fn ($recipes) => $recipes->first()->is($latest)
                && $recipes->total() === 16 && ! str_contains($recipes->url(2), 'keyword'));
        $this->get('/?page=2')->assertOk()->assertViewHas('recipes', fn ($recipes) => $recipes->count() === 4);
        $this->get('/?page=999')->assertOk()->assertSee('Về trang 1');
        $this->get('/recipes')->assertOk()->assertSee('name="keyword"', false);
        Recipe::query()->delete();
        $this->get('/')->assertOk()->assertSee('Chưa có công thức nào được chia sẻ')
            ->assertDontSee('Xóa bộ lọc');
    }
}
