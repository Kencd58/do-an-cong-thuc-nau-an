<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeStep;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class RecipeCrudTest extends TestCase
{
    private User $owner;

    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('recipe_test', DB::selectOne('SELECT DATABASE() AS db')->db);
        DB::beginTransaction();
        $this->transactionStarted = true;
        $this->owner = User::create(['name' => 'Đầu bếp', 'email' => 'recipe-'.bin2hex(random_bytes(8)).'@example.test', 'password' => 'unused']);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        RecipeStep::flushEventListeners();
        if ($this->transactionStarted) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'title' => 'Trứng chiên hành', 'description' => 'Món ăn cho bữa sáng.', 'categoryId' => 1,
            'prepTime' => 5, 'cookTime' => 5, 'servings' => 2, 'difficulty' => 'easy',
            'ingredients' => [['ingredientId' => 7, 'quantity' => '2.125', 'unit' => 'quả', 'note' => 'Đánh đều']],
            'steps' => [['instruction' => 'Đánh trứng'], ['instruction' => 'Chiên chín']], 'tagIds' => [1, 2],
        ], $changes);
    }

    private function createRecipe(array $changes = []): Recipe
    {
        $this->actingAs($this->owner)->post('/recipes', $this->payload($changes))->assertSessionHasNoErrors()->assertSessionHas('success');

        return Recipe::where('userId', $this->owner->id)->orderByDesc('id')->firstOrFail();
    }

    private function image(): UploadedFile
    {
        // Real PNG bytes: XAMPP does not need GD for this fixture.
        return UploadedFile::fake()->createWithContent('dish.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    private function withExistingImage(): Recipe
    {
        $recipe = $this->createRecipe();
        Storage::disk('public')->put('recipes/old.png', 'old image');
        $recipe->update(['imageUrl' => '/storage/recipes/old.png', 'imageId' => 'recipes/old.png']);

        return $recipe;
    }

    public function test_rec01_create_public_recipe_and_ignore_untrusted_fields(): void
    {
        $recipe = $this->createRecipe(['userId' => 999, 'imageUrl' => 'https://evil.test/a', 'imageId' => 'forged', 'status' => 'draft']);
        $this->assertEquals($this->owner->id, $recipe->userId);
        $this->assertNull($recipe->imageId);
        $this->assertNull($recipe->imageUrl);
        $this->assertSame('2.125', $recipe->ingredients->sole()->quantity);
        $this->assertSame([1, 2], $recipe->steps->pluck('stepOrder')->all());
        $this->assertEquals([1, 2], $recipe->tags->pluck('id')->all());
        Auth::logout();
        $this->get('/recipes/'.$recipe->id)->assertOk()->assertSee('Trứng chiên hành')->assertSee('Đầu bếp')->assertSee('recipe-default.png')->assertDontSee('Sửa công thức');
        $this->get('/recipes')->assertOk()->assertSee($recipe->title);
        $this->get('/')->assertOk()->assertSee($recipe->title);
    }

    public function test_rec02_upload_stores_image_locally_and_returns_public_path(): void
    {
        $recipe = $this->createRecipe(['image' => $this->image()]);
        $this->assertStringStartsWith('recipes/', $recipe->imageId);
        $this->assertSame('/storage/'.$recipe->imageId, $recipe->imageUrl);
        Storage::disk('public')->assertExists($recipe->imageId);
        $this->get('/recipes/'.$recipe->id)->assertSee($recipe->imageUrl, false);
    }

    public function test_rec03_invalid_inputs_never_write_or_upload(): void
    {
        $count = Recipe::count();
        $cases = [
            [['title' => 'x', 'description' => '', 'categoryId' => 999999, 'servings' => 101, 'difficulty' => 'no'], ['title', 'description', 'categoryId', 'servings', 'difficulty']],
            [['prepTime' => 0, 'cookTime' => 0], ['cookTime']],
            [['prepTime' => 1441, 'cookTime' => -1], ['prepTime', 'cookTime']],
            [['ingredients' => [], 'steps' => []], ['ingredients', 'steps']],
            [['ingredients' => array_fill(0, 2, ['ingredientId' => 7, 'quantity' => 1, 'unit' => 'g'])], ['ingredients.0.ingredientId', 'ingredients.1.ingredientId']],
            [['ingredients' => [['ingredientId' => 999999, 'quantity' => '0.0001', 'unit' => 'bad']]], ['ingredients.0.ingredientId', 'ingredients.0.quantity', 'ingredients.0.unit']],
            [['ingredients' => [['ingredientId' => 7, 'quantity' => '1.1234', 'unit' => 'g', 'recipeId' => 999]]], ['ingredients.0', 'ingredients.0.quantity']],
            [['steps' => [['instruction' => 'Nấu', 'stepOrder' => 99]]], ['steps.0']],
            [['tagIds' => [1, 1]], ['tagIds.0', 'tagIds.1']],
            [['removeImage' => true], ['removeImage']],
            [['ingredients' => array_fill(0, 51, ['ingredientId' => 7, 'quantity' => 1, 'unit' => 'g']), 'steps' => array_fill(0, 31, ['instruction' => 'Nấu']), 'tagIds' => range(1, 11)], ['ingredients', 'steps', 'tagIds']],
        ];
        foreach ($cases as [$changes, $errors]) {
            $this->actingAs($this->owner)->from('/recipes/create')->post('/recipes', $this->payload($changes + ['image' => $this->image()]))
                ->assertRedirect('/recipes/create')->assertSessionHasErrors($errors)->assertSessionMissing('success');
        }
        $this->assertSame($count, Recipe::count());
    }

    public function test_rec04_update_replaces_children_reindexes_steps_and_clears_missing_tags(): void
    {
        $recipe = $this->createRecipe();
        $data = $this->payload(['title' => 'Món ăn đã sửa', 'userId' => 999, 'id' => 999,
            'ingredients' => [['ingredientId' => 11, 'quantity' => 1, 'unit' => 'nhánh']],
            'steps' => [4 => ['instruction' => 'Bước đầu'], 9 => ['instruction' => 'Bước cuối']]]);
        unset($data['tagIds']);
        $this->put('/recipes/'.$recipe->id, $data)->assertRedirect('/recipes/'.$recipe->id)->assertSessionHas('success');
        $recipe->refresh();
        $this->assertEquals($this->owner->id, $recipe->userId);
        $this->assertSame('Món ăn đã sửa', $recipe->title);
        $this->assertEquals(11, $recipe->ingredients->sole()->ingredientId);
        $this->assertSame([1, 2], $recipe->steps->pluck('stepOrder')->all());
        $this->assertSame(['Bước đầu', 'Bước cuối'], $recipe->steps->pluck('instruction')->all());
        $this->assertCount(0, $recipe->tags);
    }

    public function test_rec05_keep_replace_and_remove_image_in_correct_order(): void
    {
        $recipe = $this->withExistingImage();
        $this->put('/recipes/'.$recipe->id, $this->payload())->assertSessionHas('success');
        $this->assertSame('recipes/old.png', $recipe->fresh()->imageId);
        $this->post('/recipes/'.$recipe->id, $this->payload(['_method' => 'PUT', 'image' => $this->image(), 'steps' => [['instruction' => 'Bước mới']]]))
            ->assertSessionHasNoErrors()->assertRedirect('/recipes/'.$recipe->id);
        $newImageId = $recipe->fresh()->imageId;
        $this->assertNotSame('recipes/old.png', $newImageId);
        $this->assertSame('Bước mới', $recipe->fresh()->steps->sole()->instruction);
        Storage::disk('public')->assertMissing('recipes/old.png');
        Storage::disk('public')->assertExists($newImageId);
        $this->put('/recipes/'.$recipe->id, $this->payload(['removeImage' => 1]))->assertSessionHas('success');
        $this->assertNull($recipe->fresh()->imageId);
        $this->assertNull($recipe->fresh()->imageUrl);
        Storage::disk('public')->assertMissing($newImageId);
    }

    public function test_rec06_invalid_files_and_replace_remove_conflict(): void
    {
        $recipe = $this->withExistingImage();
        foreach ([
            ['image' => $this->image(), 'removeImage' => true],
            ['image' => UploadedFile::fake()->createWithContent('fake.png', 'not an image')],
            ['image' => UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')],
            ['image' => $this->image()->size(2049)],
        ] as $changes) {
            $this->from('/recipes/'.$recipe->id.'/edit')->post('/recipes/'.$recipe->id, $this->payload($changes + ['_method' => 'PUT']))
                ->assertRedirect('/recipes/'.$recipe->id.'/edit')->assertSessionHasErrors('image');
        }
        $this->assertSame('recipes/old.png', $recipe->fresh()->imageId);
    }

    public function test_rec06_local_upload_replaces_existing_image_without_external_service(): void
    {
        $recipe = $this->withExistingImage();
        $this->from('/recipes/'.$recipe->id.'/edit')->post('/recipes/'.$recipe->id, $this->payload(['_method' => 'PUT', 'title' => 'Changed', 'image' => $this->image()]))
            ->assertSessionHasNoErrors()->assertRedirect('/recipes/'.$recipe->id);
        $updated = $recipe->fresh();
        $this->assertSame('Changed', $updated->title);
        $this->assertStringStartsWith('recipes/', $updated->imageId);
        Storage::disk('public')->assertMissing('recipes/old.png');
        Storage::disk('public')->assertExists($updated->imageId);
    }

    public function test_rec07_cleanup_failure_reports_success_with_warning(): void
    {
        $recipe = $this->withExistingImage();
        Storage::shouldReceive('disk')->twice()->with('public')->andThrow(new \RuntimeException('local disk unavailable'));
        $this->put('/recipes/'.$recipe->id, $this->payload(['removeImage' => true]))
            ->assertRedirect('/recipes/'.$recipe->id)->assertSessionHas('success')->assertSessionHas('warning', 'Công thức đã cập nhật nhưng ảnh cũ chưa dọn được.');
        $this->assertNull($recipe->fresh()->imageId);
        $recipe->update(['imageId' => 'recipes/delete-me.png']);
        $this->delete('/recipes/'.$recipe->id)->assertRedirect('/recipes')->assertSessionHas('success')
            ->assertSessionHas('warning', 'Công thức đã xóa nhưng ảnh cũ chưa dọn được.');
        $this->assertNull($recipe->fresh());
    }

    public function test_rec08_guests_redirect_and_other_users_forbidden_before_validation(): void
    {
        $recipe = $this->createRecipe();
        Auth::logout();
        foreach ([['get', '/recipes/create'], ['post', '/recipes'], ['get', '/recipes/'.$recipe->id.'/edit'],
            ['put', '/recipes/'.$recipe->id], ['delete', '/recipes/'.$recipe->id], ['get', '/my/recipes']] as [$method, $url]) {
            $this->$method($url)->assertRedirect('/login');
        }
        $other = User::create(['name' => 'B', 'email' => 'b-'.bin2hex(random_bytes(8)).'@example.test', 'password' => 'unused']);
        $this->actingAs($other)->get('/recipes/'.$recipe->id.'/edit')->assertForbidden();
        $this->put('/recipes/'.$recipe->id, [])->assertForbidden();
        $this->post('/recipes/'.$recipe->id, ['_method' => 'PUT', 'image' => $this->image()])->assertForbidden();
        $this->delete('/recipes/'.$recipe->id)->assertForbidden();
        $this->get('/recipes/'.$recipe->id)->assertOk()->assertDontSee('Sửa công thức')->assertDontSee('Xóa công thức');
        $this->get('/my/recipes')->assertOk()->assertDontSee($recipe->title);
    }

    public function test_rec09_delete_cascades_without_changing_catalog(): void
    {
        $recipe = $this->withExistingImage();
        $catalog = [Category::count(), Tag::count(), Ingredient::count()];
        $this->delete('/recipes/'.$recipe->id)->assertRedirect('/recipes');
        Storage::disk('public')->assertMissing('recipes/old.png');
        foreach (['recipe_ingredients', 'recipe_steps', 'recipe_tag'] as $table) {
            $this->assertDatabaseMissing($table, ['recipeId' => $recipe->id]);
        }
        $this->assertSame($catalog, [Category::count(), Tag::count(), Ingredient::count()]);
    }

    public function test_rec10_missing_ids_and_escaped_content(): void
    {
        $recipe = $this->createRecipe(['title' => '<script>alert(1)</script>']);
        $this->get('/recipes/'.$recipe->id)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        foreach (['/recipes/99999999', '/recipes/nope', '/recipes/99999999/edit', '/recipes/'.$recipe->id.'/image'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->put('/recipes/99999999', $this->payload())->assertNotFound();
        $this->delete('/recipes/99999999')->assertNotFound();
    }

    public function test_rec11_old_input_forms_and_catalog_relations(): void
    {
        $recipe = $this->createRecipe();
        $this->get('/recipes/create')->assertOk()->assertSee('multipart/form-data')->assertSee('name="_token"', false)->assertDontSee('name="removeImage"', false);
        $this->get('/recipes/'.$recipe->id.'/edit')->assertOk()->assertSee('value="PUT"', false)->assertSee('value="2.125"', false);
        $this->from('/recipes/'.$recipe->id.'/edit')->put('/recipes/'.$recipe->id, $this->payload(['title' => '', 'tagIds' => [], 'ingredients' => [
            ['ingredientId' => 7, 'quantity' => 2, 'unit' => 'quả', 'note' => 'Giữ ghi chú'],
            ['ingredientId' => 11, 'quantity' => 1, 'unit' => 'nhánh'],
        ]]))->assertSessionHasErrors('title')->assertSessionHasInput('ingredients.0.note', 'Giữ ghi chú');
        $this->get('/recipes/'.$recipe->id.'/edit')->assertOk()->assertSee('Giữ ghi chú')->assertSee('ingredients[1][ingredientId]', false)
            ->assertSee('vui lòng chọn lại file ảnh')->assertDontSee('checked', false);
        $this->assertTrue($recipe->category->recipes->contains($recipe));
        $this->assertTrue($recipe->tags->first()->recipes->contains($recipe));
        $this->assertTrue($recipe->ingredients->first()->ingredient->recipeIngredients->firstWhere('recipeId', $recipe->id)->recipe->is($recipe));
        $this->assertTrue($recipe->steps->first()->recipe->is($recipe));
    }

    public function test_lists_paginate_twelve_and_sort_by_creation_then_id(): void
    {
        $base = $this->createRecipe();
        for ($i = 0; $i < 12; $i++) {
            $copy = $base->replicate();
            $copy->title = 'Danh sách '.$i;
            $copy->save();
            $copy->timestamps = false;
            $copy->update(['createdAt' => '2025-01-01 00:00:00']);
        }
        $base->timestamps = false;
        $base->update(['createdAt' => '2025-01-01 00:00:00']);
        $this->get('/my/recipes')->assertViewHas('recipes', fn ($recipes) => $recipes->count() === 12 && $recipes->first()->title === 'Danh sách 11');
        $this->get('/my/recipes?page=2')->assertViewHas('recipes', fn ($recipes) => $recipes->count() === 1 && $recipes->first()->id === $base->id);
        $this->get('/recipes')->assertViewHas('recipes', fn ($recipes) => $recipes->perPage() === 12);
    }

    public function test_partial_save_failure_has_no_false_success_or_image_cleanup(): void
    {
        $recipe = $this->withExistingImage();
        RecipeStep::creating(fn () => throw new \RuntimeException('Simulated write failure'));
        Log::shouldReceive('error')->once()->with('Recipe operation incomplete.', Mockery::on(fn ($context) => $context['operation'] === 'update' && ! isset($context['message'])));
        $this->from('/recipes/'.$recipe->id.'/edit')->put('/recipes/'.$recipe->id, $this->payload(['title' => 'Đã ghi một phần', 'removeImage' => true]))
            ->assertSessionHas('error', 'Thao tác chưa hoàn tất, vui lòng kiểm tra lại.')->assertSessionMissing('success');
        $this->assertSame('Đã ghi một phần', $recipe->fresh()->title);
        $this->assertCount(0, $recipe->fresh()->steps);
    }

    public function test_local_image_storage_needs_no_external_configuration(): void
    {
        $this->createRecipe();
        $recipe = $this->createRecipe(['title' => 'Món có ảnh local', 'image' => $this->image()]);
        $this->assertStringStartsWith('/storage/recipes/', $recipe->imageUrl);
        Storage::disk('public')->assertExists($recipe->imageId);
    }

    public function test_invalid_input_types_can_be_redisplayed_without_server_error(): void
    {
        $this->actingAs($this->owner)->from('/recipes/create')->post('/recipes', $this->payload([
            'title' => ['bad'], 'description' => ['bad'], 'servings' => ['bad'],
            'ingredients' => [['ingredientId' => 7, 'quantity' => ['bad'], 'unit' => 'g', 'note' => ['bad']]],
            'steps' => [['instruction' => ['bad']]],
        ]))->assertSessionHasErrors(['title', 'description', 'servings', 'ingredients.0.quantity', 'steps.0.instruction']);
        $this->get('/recipes/create')->assertOk()->assertSee('field-error', false);
    }
}
