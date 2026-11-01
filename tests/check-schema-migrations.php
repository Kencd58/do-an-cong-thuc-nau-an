<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Always use a newly created local database; never migrate the application's database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.connections.mysql.host') !== '127.0.0.1') {
    throw new RuntimeException('Schema verification requires a local database server.');
}
$database = 'recipe_migration_'.bin2hex(random_bytes(6));
$originalConnection = DB::getDefaultConnection();
DB::statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

try {
    config(['database.connections.schema_check' => array_replace(config('database.connections.mysql'), ['database' => $database, 'url' => null])]);
    DB::setDefaultConnection('schema_check');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $database) {
        throw new RuntimeException('Unsafe schema verification target.');
    }
    $run = function (string $command, array $options = []): void {
        $exit = Artisan::call($command, $options + ['--database' => 'schema_check', '--force' => true]);
        echo Artisan::output();
        if ($exit !== 0) {
            throw new RuntimeException('Schema verification command failed: '.$command);
        }
    };
    $snapshot = function (): array {
        $result = [];
        foreach (['users', 'recipes', 'categories', 'tags', 'ingredients', 'recipe_steps', 'recipe_ingredients', 'recipe_tag', 'ratings', 'comments'] as $table) {
            $query = DB::table($table)->orderBy($table === 'recipe_tag' ? 'recipeId' : 'id');
            if ($table === 'recipe_tag') {
                $query->orderBy('tagId');
            }
            $rows = $query->get();
            $result[$table] = ['count' => $rows->count(), 'sha256' => hash('sha256', $rows->toJson()),
                'schema' => (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`')];
        }

        return $result;
    };
    $run('migrate', ['--seed' => true]);
    if (count(DB::select('SHOW TABLES')) !== 11 || DB::table('migrations')->count() !== 10) {
        throw new RuntimeException('Expected 10 business tables and 10 recorded migrations.');
    }
    foreach (['categories' => 5, 'tags' => 10, 'ingredients' => 30] as $table => $count) {
        if (DB::table($table)->count() !== $count) {
            throw new RuntimeException('Unexpected catalog count: '.$table);
        }
    }
    $constraints = DB::selectOne("SELECT COUNT(*) AS total, SUM(DELETE_RULE='CASCADE') AS cascades, SUM(DELETE_RULE='RESTRICT') AS restricts FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()");
    if ((int) $constraints->total !== 11 || (int) $constraints->cascades !== 5 || (int) $constraints->restricts !== 6) {
        throw new RuntimeException('Foreign key contracts changed.');
    }
    $unique = DB::selectOne("SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_TYPE='UNIQUE'");
    if ((int) $unique->total !== 7) {
        throw new RuntimeException('Unique constraints changed.');
    }
    // Compare catalog content, excluding the intentional updatedAt refresh during seed.
    $catalog = fn () => [DB::table('categories')->orderBy('id')->get(['id', 'name'])->toJson(),
        DB::table('tags')->orderBy('id')->get(['id', 'name'])->toJson(),
        DB::table('ingredients')->orderBy('id')->get(['id', 'name', 'defaultUnit'])->toJson()];
    $beforeCatalog = $catalog();
    $run('db:seed');
    if ($beforeCatalog !== $catalog()) {
        throw new RuntimeException('Catalog seed duplicated or changed catalog content.');
    }
    $before = $snapshot();
    $run('migrate');
    if ($before !== $snapshot() || DB::table('migrations')->count() !== 10) {
        throw new RuntimeException('Repeated migration changed data or schema.');
    }
    $run('migrate:rollback');
    if (count(DB::select('SHOW TABLES')) !== 1 || DB::table('migrations')->count() !== 0) {
        throw new RuntimeException('Fresh-database rollback did not remove all business tables.');
    }
    // Simulate an older installation with eight existing tables and no baseline history.
    foreach (glob(__DIR__.'/../database/migrations/2026_10_10_*_create_*_table.php') as $file) {
        (require $file)->up();
    }
    $run('db:seed');
    $user = DB::table('users')->insertGetId(['name' => 'Migration fixture', 'email' => 'migration@example.test', 'password' => 'unused']);
    $recipe = DB::table('recipes')->insertGetId(['userId' => $user, 'categoryId' => 1, 'title' => 'Công thức có trước migration',
        'description' => 'Giữ nguyên dữ liệu', 'prepTime' => 5, 'cookTime' => 10, 'servings' => 2, 'difficulty' => 'easy',
        'imageUrl' => '/storage/recipes/existing-image.png', 'imageId' => 'recipes/existing-image.png']);
    DB::table('recipe_steps')->insert(['recipeId' => $recipe, 'stepOrder' => 1, 'instruction' => 'Nấu chín']);
    DB::table('recipe_ingredients')->insert(['recipeId' => $recipe, 'ingredientId' => 1, 'quantity' => 100, 'unit' => 'g']);
    DB::table('recipe_tag')->insert(['recipeId' => $recipe, 'tagId' => 1]);
    // Existing interaction migrations were already recorded in the old installation.
    $run('migrate', ['--path' => 'database/migrations/2026_10_11_000001_create_ratings_table.php']);
    $run('migrate', ['--path' => 'database/migrations/2026_10_11_000002_create_comments_table.php']);
    DB::table('ratings')->insert(['recipeId' => $recipe, 'userId' => $user, 'score' => 4]);
    DB::table('comments')->insert(['recipeId' => $recipe, 'userId' => $user, 'content' => 'Bình luận có trước migration']);
    $before = $snapshot();
    $run('migrate');
    $run('migrate');
    if ($before !== $snapshot() || DB::table('migrations')->count() !== 10) {
        throw new RuntimeException('Adopting existing tables changed retained data or schema.');
    }
    echo "PASS: migration-only installation, foreign/unique keys, repeatable catalogs, fresh rollback and legacy adoption preserve data and schema.\n";
} finally {
    DB::disconnect('schema_check');
    DB::setDefaultConnection($originalConnection);
    DB::statement('DROP DATABASE `'.$database.'`');
    echo "Temporary schema verification database removed.\n";
}
