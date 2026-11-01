<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Recipe;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function store(Request $request, Recipe $recipe): RedirectResponse
    {
        return $this->save($request, $recipe);
    }

    public function update(Request $request, Recipe $recipe, Comment $comment): RedirectResponse
    {
        $this->authorizeComment($request, $recipe, $comment);

        return $this->save($request, $recipe, $comment);
    }

    public function destroy(Request $request, Recipe $recipe, Comment $comment): RedirectResponse
    {
        $this->authorizeComment($request, $recipe, $comment);
        $url = route('recipes.show', $recipe).'#comments';
        try {
            $comment->delete();
        } catch (QueryException $exception) {
            $this->logFailure($exception, $recipe, $comment, 'delete');

            return redirect($url)->with('error', 'Chưa xóa được bình luận. Vui lòng thử lại.');
        }

        return redirect($url)->with('success', 'Đã xóa bình luận.');
    }

    private function authorizeComment(Request $request, Recipe $recipe, Comment $comment): void
    {
        abort_unless($comment->recipeId === (int) $recipe->id, 404);
        abort_unless($comment->userId === (int) $request->user()->id, 403);
    }

    private function save(Request $request, Recipe $recipe, ?Comment $comment = null): RedirectResponse
    {
        $content = $request->input('content');
        $input = ['content' => is_string($content) ? trim($content) : $content];
        $validator = Validator::make($input, ['content' => ['required', 'string', 'max:2000']], [], ['content' => 'nội dung bình luận']);
        $bag = $comment ? 'commentUpdate_'.$comment->id : 'commentCreate';
        $page = filter_var($request->input('commentsPage'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $url = route('recipes.show', ['recipe' => $recipe, 'commentsPage' => $page]).'#comments';
        if ($validator->fails()) {
            return redirect($url)->withErrors($validator, $bag)->withInput($input);
        }
        try {
            if ($comment) {
                $comment->update($validator->validated());
            } else {
                $recipe->comments()->create($validator->validated() + ['userId' => $request->user()->id]);
            }
        } catch (QueryException $exception) {
            $this->logFailure($exception, $recipe, $comment, 'save');

            return redirect($url)->withErrors(['content' => 'Chưa lưu được bình luận. Vui lòng thử lại.'], $bag)->withInput($input);
        }

        return redirect(route('recipes.show', $recipe).'#comments')->with('success', 'Đã lưu bình luận.');
    }

    private function logFailure(QueryException $exception, Recipe $recipe, ?Comment $comment, string $operation): void
    {
        Log::error('Comment write failed.', ['operation' => $operation, 'recipeId' => $recipe->id, 'commentId' => $comment?->id,
            'sqlState' => $exception->errorInfo[0] ?? null, 'driverCode' => $exception->errorInfo[1] ?? null]);
    }
}
