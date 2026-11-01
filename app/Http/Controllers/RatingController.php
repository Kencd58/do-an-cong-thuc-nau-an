<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Recipe;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
{
    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_if((int) $request->user()->id === (int) $recipe->userId, 403);
        $url = route('recipes.show', $recipe).'#ratings';
        try {
            $data = $request->validateWithBag('rating', ['score' => ['required', 'integer', 'between:1,5']], [], ['score' => 'điểm đánh giá']);
        } catch (ValidationException $exception) {
            return redirect($url)->withErrors($exception->validator, 'rating')->withInput($request->only('score'));
        }
        try {
            Rating::upsert([
                ['recipeId' => $recipe->id, 'userId' => $request->user()->id, 'score' => $data['score']],
            ], ['recipeId', 'userId'], ['score', 'updatedAt']);
        } catch (QueryException $exception) {
            $this->logFailure($exception, $recipe, 'update');

            return redirect($url)->withErrors(['score' => 'Chưa lưu được đánh giá. Vui lòng thử lại.'], 'rating')
                ->withInput($request->only('score'));
        }

        return redirect($url)->with('success', 'Đã lưu đánh giá của bạn.');
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_if((int) $request->user()->id === (int) $recipe->userId, 403);
        $url = route('recipes.show', $recipe).'#ratings';
        try {
            $recipe->ratings()->where('userId', $request->user()->id)->delete();
        } catch (QueryException $exception) {
            $this->logFailure($exception, $recipe, 'delete');

            return redirect($url)->with('error', 'Chưa gỡ được đánh giá. Vui lòng thử lại.');
        }

        return redirect($url)->with('success', 'Đã gỡ đánh giá của bạn.');
    }

    private function logFailure(QueryException $exception, Recipe $recipe, string $operation): void
    {
        Log::error('Rating write failed.', ['operation' => $operation, 'recipeId' => $recipe->id,
            'sqlState' => $exception->errorInfo[0] ?? null, 'driverCode' => $exception->errorInfo[1] ?? null]);
    }
}
