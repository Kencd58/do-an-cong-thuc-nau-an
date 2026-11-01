<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Usage: php tests/check-fresh-install.php EMPTY_UNPACKED_SOURCE COMPOSER_PHAR
// Creates/drops only a random recipe_install_* database; keeps the checkout for inspection.
$processEnvironment = getenv();
foreach (array_keys($processEnvironment) as $key) {
    if (preg_match('/^(APP_|DB_|SESSION_|CACHE_)/', $key)) {
        unset($processEnvironment[$key]);
    }
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $exception): void {
    fwrite(STDERR, 'Fresh-install check failed: '.get_class($exception).". Review the preceding command output.\n");
    exit(1);
});
$target = realpath($argv[1] ?? '');
$composer = realpath($argv[2] ?? '');
if (! $target || $target === realpath(__DIR__.'/..') || ! is_file($target.'/artisan')
    || file_exists($target.'/.env') || is_dir($target.'/vendor') || ! $composer) {
    throw new RuntimeException('Provide a fresh unpacked source (no .env/vendor) and composer.phar.');
}
if (config('database.connections.mysql.host') !== '127.0.0.1') {
    throw new RuntimeException('Fresh-install verification requires a local database server.');
}
$database = 'recipe_install_'.bin2hex(random_bytes(6));
$config = config('database.connections.mysql');
$processEnvironment['DB_DATABASE'] = $database;
$processEnvironment['DB_HOST'] = '127.0.0.1';
$processEnvironment['DB_URL'] = '';
$run = function (array $command) use ($target, $processEnvironment): string {
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, $target, $processEnvironment);
    if (! is_resource($process)) {
        throw new RuntimeException('Cannot start installation command.');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $status = proc_close($process);
    echo $output;
    if ($status !== 0) {
        throw new RuntimeException('Installation command returned an unexpected exit code.');
    }

    return $output;
};

DB::statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    $env = file_get_contents($target.'/.env.example');
    foreach (['DB_HOST' => '127.0.0.1', 'DB_PORT' => $config['port'], 'DB_DATABASE' => $database,
        'DB_USERNAME' => $config['username'], 'DB_PASSWORD' => $config['password']] as $key => $value) {
        $env = preg_replace_callback('/^'.$key.'=.*$/m', fn () => $key.'="'.addcslashes((string) $value, '\\"$').'"', $env);
    }
    file_put_contents($target.'/.env', $env);
    $run([PHP_BINARY, $composer, 'install', '--no-interaction', '--prefer-dist']);
    $run([PHP_BINARY, $composer, 'check-platform-reqs']);
    $run([PHP_BINARY, 'artisan', 'key:generate', '--no-ansi']);
    $run([PHP_BINARY, '-r', 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); if (Illuminate\Support\Facades\DB::selectOne("SELECT DATABASE() AS db")->db !== getenv("DB_DATABASE") || !str_starts_with(getenv("DB_DATABASE"), "recipe_install_") || Illuminate\Support\Facades\DB::select("SHOW TABLES") !== []) { fwrite(STDERR, "Unsafe fresh-install target"); exit(1); } echo "Verified empty temporary DB before any schema/seed writes.\n";']);
    $run([PHP_BINARY, 'artisan', 'migrate', '--seed', '--no-ansi']);
    $run([PHP_BINARY, 'artisan', 'db:seed', '--no-ansi']);
    $run([PHP_BINARY, 'artisan', 'db:seed', '--class=DemoSeeder', '--no-ansi']);
    config(['database.connections.fresh_check' => array_replace($config, ['database' => $database, 'url' => null])]);
    $connection = DB::connection('fresh_check');
    $snapshot = function () use ($connection): array {
        $data = [];
        foreach (['users', 'recipes', 'recipe_ingredients', 'recipe_steps', 'recipe_tag', 'ratings', 'comments'] as $table) {
            $query = $connection->table($table);
            $table === 'recipe_tag' ? $query->orderBy('recipeId')->orderBy('tagId') : $query->orderBy('id');
            $data[$table] = hash('sha256', $query->get()->toJson());
        }

        return $data;
    };
    $before = $snapshot();
    $run([PHP_BINARY, 'artisan', 'db:seed', '--class=DemoSeeder', '--no-ansi']);
    if ($before !== $snapshot()) {
        throw new RuntimeException('DemoSeeder changed data on its second run.');
    }
    $run([PHP_BINARY, 'artisan', 'migrate', '--no-ansi']);
    if ($before !== $snapshot() || $connection->table('migrations')->count() !== 10) {
        throw new RuntimeException('Repeated migration changed data or migration history.');
    }
    foreach (['users' => 2, 'recipes' => 30, 'categories' => 5, 'tags' => 10, 'ingredients' => 30,
        'ratings' => 30, 'comments' => 15, 'recipe_steps' => 60, 'recipe_ingredients' => 60, 'recipe_tag' => 60] as $table => $count) {
        if ($connection->table($table)->count() !== $count) {
            throw new RuntimeException('Unexpected fresh-install count for '.$table);
        }
    }
    if (count($connection->select('SHOW TABLES')) !== 11) {
        throw new RuntimeException('Expected 10 business tables and migrations.');
    }
    $npm = null;
    foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $directory) {
        if (is_file($directory.'/npm.cmd')) {
            $npm = $directory.'/npm.cmd';
            break;
        }
    }
    if (! $npm) {
        throw new RuntimeException('npm.cmd was not found on PATH.');
    }
    $run(['cmd', '/d', '/c', $npm, 'ci', '--no-audit', '--no-fund']);
    $run(['cmd', '/d', '/c', $npm, 'run', 'build']);
    $run([PHP_BINARY, 'artisan', 'view:cache', '--no-ansi']);
    $run([PHP_BINARY, 'artisan', 'route:list', '--except-vendor', '--no-ansi']);
    $run([PHP_BINARY, '-r', 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class); foreach (["/recipes", "/recipes/1", "/recipes?page=3", "/register", "/login"] as $url) { $request = Illuminate\Http\Request::create("http://localhost".$url); $response = $kernel->handle($request); if ($response->getStatusCode() !== 200) { fwrite(STDERR, "HTTP smoke failed: ".$url); exit(1); } $kernel->terminate($request, $response); } echo "PASS: fresh HTTP kernel renders list/detail/page3/auth with status 200.\n";']);
    echo "PASS: fresh dependencies, migrations, catalogs, demo rerun, unchanged data on repeated migration, assets and Blade compilation.\n";
    echo "NOTE: recipe images use the local public disk; no external image API is required.\n";
} finally {
    DB::disconnect('fresh_check');
    DB::statement('DROP DATABASE `'.$database.'`');
    // Remove only the credentials file created by this script; retain the unpacked source.
    if (is_file($target.'/.env')) {
        unlink($target.'/.env');
    }
    echo "Temporary installation database and credentials removed.\n";
}
