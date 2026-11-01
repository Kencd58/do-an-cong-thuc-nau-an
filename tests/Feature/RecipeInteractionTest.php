<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RecipeInteractionTest extends TestCase
{
    private User $owner;

    private User $writer;

    private Recipe $recipe;

    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('recipe_test', DB::selectOne('SELECT DATABASE() AS db')->db);
        DB::beginTransaction();
        $this->transactionStarted = true;
        $this->owner = $this->user('Owner');
        $this->writer = $this->user('Writer');
        $this->recipe = $this->recipe();
    }

    protected function tearDown(): void
    {
        Comment::flushEventListeners();
        if ($this->transactionStarted) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function user(string $name): User
    {
        return User::create(['name' => $name, 'email' => bin2hex(random_bytes(10)).'@example.test', 'password' => 'unused']);
    }

    private function recipe(): Recipe
    {
        return $this->owner->recipes()->create(['title' => 'Công thức tương tác', 'description' => 'Món ngon',
            'categoryId' => 1, 'prepTime' => 5, 'cookTime' => 10, 'servings' => 2, 'difficulty' => 'easy']);
    }

    private function url(string $suffix = ''): string
    {
        return route('recipes.show', $this->recipe).$suffix;
    }

    private function comment(string $content = 'Bình luận ban đầu'): Comment
    {
        return $this->recipe->comments()->create(['userId' => $this->writer->id, 'content' => $content]);
    }

    public function test_int01_ratings_are_independent_and_aggregates_are_read_from_database(): void
    {
        $this->get($this->url())->assertOk()->assertSee('Chưa có đánh giá')->assertViewHas('myRating', null);
        foreach (range(1, 5) as $score) {
            $voter = $this->user('Voter '.$score);
            $this->actingAs($voter)->put($this->url('/rating'), ['score' => $score, 'userId' => $this->owner->id, 'recipeId' => 999])
                ->assertRedirect($this->url('#ratings'))->assertSessionHas('success');
            $this->assertDatabaseHas('ratings', ['userId' => $voter->id, 'recipeId' => $this->recipe->id, 'score' => $score]);
        }
        $this->get($this->url())->assertViewHas('ratingCount', 5)->assertViewHas('averageRating', 3)
            ->assertViewHas('myRating', fn ($rating) => $rating->score === 5)->assertSee('3.0 / 5');
        $this->assertSame(0, $this->recipe->comments()->count());
        $rating = $this->recipe->ratings()->first();
        $this->assertSame($this->recipe->id, $rating->recipe->id);
        $this->assertSame($rating->userId, $rating->user->id);
        $this->assertSame(1, $rating->user->ratings()->count());
    }

    public function test_int02_repeated_upserts_keep_one_row_and_created_timestamp_and_delete_is_idempotent(): void
    {
        $this->actingAs($this->writer)->put($this->url('/rating'), ['score' => 2])->assertSessionHas('success');
        $before = $this->recipe->ratings()->sole();
        $this->travel(5)->seconds();
        foreach ([4, 4, 4] as $score) {
            $this->put($this->url('/rating'), ['score' => $score])->assertSessionHas('success');
        }
        $after = $this->recipe->ratings()->sole();
        $this->assertSame($before->id, $after->id);
        $this->assertTrue($before->createdAt->eq($after->createdAt));
        $this->assertTrue($after->updatedAt->gt($before->updatedAt));
        $this->assertSame(4, $after->score);
        try {
            DB::table('ratings')->insert(['recipeId' => $this->recipe->id, 'userId' => $this->writer->id, 'score' => 3]);
            $this->fail('Database must reject duplicate rating.');
        } catch (QueryException $exception) {
            $this->assertSame(1062, $exception->errorInfo[1]);
        }
        foreach ([1, 2] as $_) {
            $this->delete($this->url('/rating'), ['userId' => $this->owner->id])->assertRedirect($this->url('#ratings'))->assertSessionHas('success');
        }
        $this->get($this->url())->assertViewHas('ratingCount', 0)->assertViewHas('averageRating', null);
    }

    public function test_int03_owner_is_forbidden_before_validation_and_scores_use_rating_bag(): void
    {
        $this->actingAs($this->owner)->put($this->url('/rating'), [])->assertForbidden();
        $this->delete($this->url('/rating'))->assertForbidden();
        foreach ([null, 0, 6, 1.5, [], 'wrong'] as $score) {
            $this->actingAs($this->writer)->from('/recipes')->put($this->url('/rating'), ['score' => $score])
                ->assertRedirect($this->url('#ratings'))->assertSessionHasErrors('score', null, 'rating');
            $this->get($this->url())->assertOk()->assertSee('rating-error', false);
        }
        $this->assertSame(0, $this->recipe->ratings()->count());
    }

    public function test_int04_many_comments_trim_content_and_reject_invalid_input(): void
    {
        foreach (['  Ngon quá  ', str_repeat('ế', 2000)] as $content) {
            $this->actingAs($this->writer)->post($this->url('/comments'), ['content' => $content, 'userId' => $this->owner->id, 'recipeId' => 999])
                ->assertRedirect($this->url('#comments'))->assertSessionHas('success');
            $this->assertDatabaseHas('comments', ['recipeId' => $this->recipe->id, 'userId' => $this->writer->id, 'content' => trim($content)]);
        }
        foreach ([null, " \n\t ", str_repeat('ế', 2001), ['invalid']] as $content) {
            $this->post($this->url('/comments'), ['content' => $content])->assertSessionHasErrors('content', null, 'commentCreate');
            $this->get($this->url())->assertOk();
        }
        $this->assertSame(2, $this->recipe->comments()->count());
        $this->assertSame(0, $this->recipe->ratings()->count());
        $this->actingAs($this->owner)->post($this->url('/comments'), ['content' => 'Chủ công thức cũng bình luận'])->assertSessionHas('success');
    }

    public function test_int05_only_comment_author_can_edit_delete_and_edit_preserves_owner_and_recipe(): void
    {
        $comment = $this->comment();
        foreach ([$this->owner, $this->user('Other')] as $user) {
            $this->actingAs($user)->put($this->url('/comments/'.$comment->id), [])->assertForbidden();
            $this->delete($this->url('/comments/'.$comment->id))->assertForbidden();
            $this->get($this->url())->assertDontSee('Sửa bình luận')->assertDontSee('Xóa bình luận');
        }
        $this->travel(5)->seconds();
        $this->actingAs($this->writer)->put($this->url('/comments/'.$comment->id), ['content' => '  Đã nấu thử  ', 'userId' => $this->owner->id, 'recipeId' => 999, 'commentsPage' => 2])
            ->assertRedirect($this->url('#comments'))->assertSessionHas('success');
        $this->assertSame('Đã nấu thử', $comment->fresh()->content);
        $this->assertSame($this->writer->id, $comment->fresh()->userId);
        $this->assertSame($this->recipe->id, $comment->fresh()->recipeId);
        $this->get($this->url())->assertSee('Đã chỉnh sửa');
        $this->delete($this->url('/comments/'.$comment->id))->assertRedirect($this->url('#comments'));
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_int06_cross_recipe_and_missing_ids_are_404_before_validation(): void
    {
        $comment = $this->comment();
        $other = $this->recipe();
        foreach ([$this->writer, $this->owner] as $user) {
            $this->actingAs($user)->put('/recipes/'.$other->id.'/comments/'.$comment->id, [])->assertNotFound();
            $this->delete('/recipes/'.$other->id.'/comments/'.$comment->id)->assertNotFound();
        }
        foreach (['999999999', 'abc'] as $id) {
            $this->put('/recipes/'.$id.'/rating', ['score' => 3])->assertNotFound();
            $this->delete('/recipes/'.$id.'/rating')->assertNotFound();
            $this->post('/recipes/'.$id.'/comments', ['content' => 'Hello'])->assertNotFound();
            $this->put($this->url('/comments/'.$id), [])->assertNotFound();
            $this->delete($this->url('/comments/'.$id))->assertNotFound();
        }
        $this->assertSame('Bình luận ban đầu', $comment->fresh()->content);
    }

    public function test_int07_guest_can_read_but_all_five_writes_require_login(): void
    {
        $comment = $this->comment();
        $this->recipe->ratings()->create(['userId' => $this->writer->id, 'score' => 4]);
        Auth::logout();
        $this->get($this->url())->assertOk()->assertSee('4.0 / 5')->assertSee($comment->content)
            ->assertDontSee('Gửi đánh giá')->assertDontSee('Gửi bình luận');
        foreach ([['PUT', '/rating'], ['DELETE', '/rating'], ['POST', '/comments'], ['PUT', '/comments/'.$comment->id], ['DELETE', '/comments/'.$comment->id]] as [$method, $path]) {
            $this->call($method, $this->url($path))->assertRedirect(route('login'));
        }
    }

    public function test_int08_pagination_is_stable_and_failed_edit_only_restores_its_own_form(): void
    {
        $rows = collect();
        foreach (range(1, 12) as $i) {
            $rows->push($this->comment('Bình luận '.$i));
        }
        Comment::whereIn('id', $rows->pluck('id'))->update(['createdAt' => now()->subDay(), 'updatedAt' => now()->subDay()]);
        $newest = $rows->first();
        $newest->refresh()->forceFill(['createdAt' => now()])->save();
        $this->actingAs($this->writer)->get($this->url())->assertViewHas('comments', fn ($comments) => $comments->count() === 10 && $comments->first()->id === $newest->id)
            ->assertSee('commentsPage=2#comments', false);
        $page = $this->get($this->url('?commentsPage=2'))->assertOk()->viewData('comments');
        $this->assertSame([$rows[2]->id, $rows[1]->id], $page->pluck('id')->all());
        $target = $rows[2];
        $invalid = str_repeat('x', 2001);
        $this->put($this->url('/comments/'.$target->id), ['content' => $invalid, 'commentsPage' => 2])
            ->assertRedirect($this->url('?commentsPage=2#comments'))
            ->assertSessionHasErrors('content', null, 'commentUpdate_'.$target->id);
        $html = $this->get($this->url('?commentsPage=2'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, $invalid));
        $this->assertStringContainsString('>'.$rows[1]->content.'</textarea>', $html);
        $this->assertStringContainsString('></textarea>', $html);
        $this->assertSame($target->content, $target->fresh()->content);
        $this->get($this->url('?commentsPage=999'))->assertSee('Trang này không có bình luận')->assertSee('Về trang 1');
        $this->post($this->url('/comments'), ['content' => [], 'commentsPage' => []])->assertRedirect($this->url('?commentsPage=1#comments'));
        $this->get($this->url())->assertOk();
    }

    public function test_int09_script_and_names_are_escaped_for_guests_and_edit_forms(): void
    {
        $script = '<script>window.interactionXss=1</script>';
        $comment = $this->comment($script);
        $this->writer->update(['name' => $script]);
        $this->get($this->url())->assertSee(e($script), false)->assertDontSee($script, false);
        $this->actingAs($this->writer)->get($this->url())->assertSee(e($script), false)->assertDontSee($script, false);
        $this->assertSame($this->writer->id, $comment->user->id);
        $this->assertSame($this->recipe->id, $comment->recipe->id);
        $this->assertSame(1, $this->writer->comments()->count());
    }

    public function test_int10_recipe_delete_cascades_only_its_interactions_and_users_are_restricted(): void
    {
        $comment = $this->comment();
        $rating = $this->recipe->ratings()->create(['userId' => $this->writer->id, 'score' => 5]);
        $other = $this->recipe();
        $otherComment = $other->comments()->create(['userId' => $this->writer->id, 'content' => 'Giữ nguyên']);
        $otherRating = $other->ratings()->create(['userId' => $this->writer->id, 'score' => 2]);
        try {
            $this->writer->delete();
            $this->fail('User deletion must be restricted.');
        } catch (QueryException $exception) {
            $this->assertSame(1451, $exception->errorInfo[1]);
        }
        $counts = collect(['users', 'categories', 'tags', 'ingredients'])->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);
        $this->actingAs($this->owner)->delete($this->url())->assertSessionHas('success');
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
        $this->assertDatabaseHas('comments', ['id' => $otherComment->id]);
        $this->assertDatabaseHas('ratings', ['id' => $otherRating->id]);
        foreach ($counts as $table => $count) {
            $this->assertSame($count, DB::table($table)->count());
        }
    }

    public function test_database_failure_preserves_form_input_without_success_or_sensitive_logs(): void
    {
        Log::spy();
        $comment = $this->comment();
        $connection = DB::connection();
        $connection->beforeExecuting(function ($query) {
            if (preg_match('/^(insert into|update|delete from) `(ratings|comments)`/i', $query)) {
                throw new QueryException('mysql', $query, ['private-content'], new \PDOException('private database failure'));
            }
        });
        $this->actingAs($this->writer)->put($this->url('/rating'), ['score' => 3])
            ->assertSessionHasErrors('score', null, 'rating')->assertSessionMissing('success');
        $this->delete($this->url('/rating'))->assertSessionHas('error')->assertSessionMissing('success');
        $this->post($this->url('/comments'), ['content' => 'Giữ nội dung'])
            ->assertSessionHasErrors('content', null, 'commentCreate')->assertSessionHas('_old_input.content', 'Giữ nội dung')->assertSessionMissing('success');
        $this->put($this->url('/comments/'.$comment->id), ['content' => 'Chưa lưu'])
            ->assertSessionHasErrors('content', null, 'commentUpdate_'.$comment->id)->assertSessionMissing('success');
        $this->delete($this->url('/comments/'.$comment->id))->assertSessionHas('error')->assertSessionMissing('success');
        $this->assertSame('Bình luận ban đầu', $comment->fresh()->content);
        Log::shouldHaveReceived('error')->times(5)->withArgs(fn ($message, $context) => ! str_contains(json_encode([$message, $context]), 'private'));
    }
}
