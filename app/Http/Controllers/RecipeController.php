<?php

namespace App\Http\Controllers;

use App\Exceptions\RecipeUploadException;
use App\Http\Requests\ListRecipeRequest;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class RecipeController extends Controller
{
    private const FIELDS = ['title', 'description', 'categoryId', 'prepTime', 'cookTime', 'servings', 'difficulty'];

    public function home(): View
    {
        return view('recipes.home', [
            'recipes' => Recipe::with(['user', 'category', 'tags'])
                ->orderByDesc('createdAt')->orderByDesc('id')->paginate(12),
        ]);
    }

    public function index(ListRecipeRequest $request): View
    {
        $filters = $request->validated();

        return view('recipes.index', $this->catalog() + [
            'filters' => $filters, 'difficulties' => config('recipe.difficulties'),
            'recipes' => Recipe::query()->filter($filters)->with(['user', 'category', 'tags'])
                ->paginate(12)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('recipes.create', $this->catalog() + ['recipe' => new Recipe]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $image = isset($data['image']) ? $this->upload($data['image']) : ['imageUrl' => null, 'imageId' => null];
            // Sequential writes are intentional: a DB failure may leave partial data.
            $recipe = Recipe::create(Arr::only($data, self::FIELDS) + ['userId' => $request->user()->id] + $image);
            $this->saveChildren($recipe, $data);
        } catch (Throwable $exception) {
            return $this->saveError($request, $exception, 'create');
        }

        return to_route('recipes.show', $recipe)->with('success', 'Đăng công thức thành công.');
    }

    public function show(Request $request, Recipe $recipe): View
    {
        return view('recipes.show', [
            'recipe' => $recipe->load(['user', 'category', 'ingredients.ingredient', 'steps', 'tags']),
            'averageRating' => $recipe->ratings()->avg('score'),
            'ratingCount' => $recipe->ratings()->count(),
            'myRating' => $request->user() ? $recipe->ratings()->where('userId', $request->user()->id)->first() : null,
            'comments' => $recipe->comments()->with('user')->orderByDesc('createdAt')->orderByDesc('id')
                ->paginate(10, ['*'], 'commentsPage')->fragment('comments'),
        ]);
    }

    public function edit(Request $request, Recipe $recipe): View
    {
        abort_unless((int) $request->user()->id === (int) $recipe->userId, 403);

        return view('recipes.edit', $this->catalog() + ['recipe' => $recipe->load(['ingredients', 'steps', 'tags'])]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        try {
            $data = $request->validated();
            $oldImageId = $recipe->imageId;
            $fields = Arr::only($data, self::FIELDS);
            $replaceImage = isset($data['image']) || ($data['removeImage'] ?? false);
            if (isset($data['image'])) {
                $fields += $this->upload($data['image']);
            } elseif ($data['removeImage'] ?? false) {
                $fields += ['imageUrl' => null, 'imageId' => null];
            }
            $recipe->update($fields);
            $recipe->ingredients()->delete();
            $recipe->steps()->delete();
            $this->saveChildren($recipe, $data);
            $warning = $replaceImage && $oldImageId && $oldImageId !== $recipe->imageId
                ? $this->destroyImage($oldImageId, 'Công thức đã cập nhật nhưng ảnh cũ chưa dọn được.') : null;
        } catch (Throwable $exception) {
            return $this->saveError($request, $exception, 'update', $recipe->id);
        }

        return to_route('recipes.show', $recipe)->with('success', 'Cập nhật công thức thành công.')
            ->with('warning', $warning);
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_unless((int) $request->user()->id === (int) $recipe->userId, 403);
        try {
            $imageId = $recipe->imageId;
            $recipe->delete();
            $warning = $imageId ? $this->destroyImage($imageId, 'Công thức đã xóa nhưng ảnh cũ chưa dọn được.') : null;
        } catch (Throwable $exception) {
            return $this->saveError($request, $exception, 'delete', $recipe->id);
        }

        return to_route('recipes.index')->with('success', 'Xóa công thức thành công.')->with('warning', $warning);
    }

    private function catalog(): array
    {
        return ['categories' => Category::orderBy('name')->get(), 'ingredients' => Ingredient::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get()];
    }

    private function saveChildren(Recipe $recipe, array $data): void
    {
        foreach ($data['ingredients'] as $ingredient) {
            $recipe->ingredients()->create(Arr::only($ingredient, ['ingredientId', 'quantity', 'unit', 'note']));
        }
        foreach (array_values($data['steps']) as $index => $step) {
            $recipe->steps()->create(['stepOrder' => $index + 1, 'instruction' => $step['instruction']]);
        }
        $recipe->tags()->sync($data['tagIds'] ?? []);
    }

    private function upload(UploadedFile $file): array
    {
        try {
            $path = $file->store('recipes', 'public');
            if (! is_string($path) || $path === '' || strlen($path) > 255) {
                throw new \RuntimeException('Local image storage failed.');
            }

            return ['imageUrl' => '/storage/'.$path, 'imageId' => $path];
        } catch (Throwable $exception) {
            Log::warning('Recipe image storage failed.', ['exceptionType' => get_class($exception)]);
            throw new RecipeUploadException('Không thể lưu ảnh, vui lòng thử lại');
        }
    }

    private function destroyImage(string $imageId, string $warning): ?string
    {
        try {
            $disk = Storage::disk('public');
            if ($disk->exists($imageId) && ! $disk->delete($imageId)) {
                throw new \RuntimeException('Local image cleanup failed.');
            }

            return null;
        } catch (Throwable $exception) {
            Log::warning('Recipe local image cleanup failed.', ['exceptionType' => get_class($exception)]);

            return $warning;
        }
    }

    private function saveError(Request $request, Throwable $exception, string $operation, ?int $recipeId = null): RedirectResponse
    {
        if ($exception instanceof RecipeUploadException) {
            return back()->withInput($request->only(StoreRecipeRequest::INPUT_FIELDS))
                ->withErrors(['image' => $exception->getMessage()]);
        }
        // Never log SQL bindings, credentials or SDK exception messages.
        Log::error('Recipe operation incomplete.', ['operation' => $operation, 'recipeId' => $recipeId,
            'exceptionType' => get_class($exception)]);

        return back()->withInput($request->only(StoreRecipeRequest::INPUT_FIELDS))
            ->with('error', 'Thao tác chưa hoàn tất, vui lòng kiểm tra lại.');
    }
}
