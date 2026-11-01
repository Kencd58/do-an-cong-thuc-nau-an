<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// A separate local database; never run migrations/fixtures on the user's database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.testing');
$app->make(Kernel::class)->bootstrap();
$config = config('database.connections.mysql');
$pdo = new PDO(
    'mysql:host=127.0.0.1;port='.$config['port'].';charset=utf8mb4',
    $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec('CREATE DATABASE IF NOT EXISTS recipe_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE recipe_test');
config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'recipe_test', 'database.connections.mysql.host' => '127.0.0.1', 'database.connections.mysql.url' => null]);
DB::purge('mysql');
if (DB::selectOne('SELECT DATABASE() AS db')->db !== 'recipe_test') {
    throw new RuntimeException('Refusing to migrate a non-test database.');
}
if (Artisan::call('migrate', ['--seed' => true, '--force' => true]) !== 0) {
    throw new RuntimeException('Test database migration/seed failed.');
}
echo Artisan::output();
echo "recipe_test: 10 business tables and catalog ready. No application data changed.\n";
