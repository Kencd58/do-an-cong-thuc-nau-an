<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder is only available in local/testing.');
        }

        $categories = Category::orderBy('id')->limit(5)->get();
        $ingredients = Ingredient::orderBy('id')->limit(30)->get();
        $tags = Tag::orderBy('id')->limit(10)->get();
        if ($categories->count() !== 5 || $ingredients->count() !== 30 || $tags->count() !== 10) {
            throw new RuntimeException('Seed the 5/10/30 catalogs before DemoSeeder.');
        }

        DB::transaction(function () use ($categories, $ingredients, $tags): void {
            $users = [];
            foreach (['a', 'b'] as $letter) {
                $email = 'demo.'.$letter.'@example.test';
                $name = 'Demo '.strtoupper($letter);
                $user = User::where('email', $email)->first();
                // Do not take over an existing account that happens to use a demo email.
                if ($user && ($user->name !== $name || ! Hash::check('Demo12345!', $user->password))) {
                    throw new RuntimeException('A demo email is already in use by a different account. No demo data was changed.');
                }
                $users[] = $user ?? User::create(['email' => $email, 'name' => $name, 'password' => Hash::make('Demo12345!')]);
            }

            for ($index = 0; $index < 30; $index++) {
                $owner = $index < 15 ? 0 : 1;
                $recipe = Recipe::firstOrCreate([
                    'userId' => $users[$owner]->id, 'title' => sprintf('Demo Recipe %02d', $index + 1),
                ], [
                    'description' => 'Công thức mẫu để thử tìm kiếm, bộ lọc và tương tác.',
                    'categoryId' => $categories[$index % 5]->id,
                    'prepTime' => 5 + ($index % 6) * 5, 'cookTime' => 10 + ($index % 10) * 5,
                    'servings' => 1 + $index % 4, 'difficulty' => ['easy', 'medium', 'hard'][$index % 3],
                    'imageUrl' => null, 'imageId' => null,
                ]);
                foreach ([$index, ($index + 7) % 30] as $ingredientIndex) {
                    $ingredient = $ingredients[$ingredientIndex];
                    $recipe->ingredients()->firstOrCreate(['ingredientId' => $ingredient->id], [
                        'quantity' => 1 + $index % 5, 'unit' => $ingredient->defaultUnit,
                    ]);
                }
                foreach (['Chuẩn bị và sơ chế nguyên liệu.', 'Nấu chín và trình bày món ăn.'] as $step => $instruction) {
                    $recipe->steps()->firstOrCreate(['stepOrder' => $step + 1], ['instruction' => $instruction]);
                }
                $recipe->tags()->syncWithoutDetaching([$tags[$index % 10]->id, $tags[($index + 3) % 10]->id]);
                $recipe->ratings()->firstOrCreate(['userId' => $users[1 - $owner]->id], ['score' => 1 + $index % 5]);
                if ($index === 0) {
                    for ($comment = 1; $comment <= 12; $comment++) {
                        $recipe->comments()->firstOrCreate([
                            'userId' => $users[1]->id, 'content' => sprintf('Demo B bình luận mẫu %02d.', $comment),
                        ]);
                    }
                }
                if ($index >= 15 && $index < 18) {
                    $recipe->comments()->firstOrCreate([
                        'userId' => $users[0]->id, 'content' => 'Demo A đã thử món này.',
                    ]);
                }
            }
        });
    }
}
