<?php

// Apply the downloaded photo catalog to the local SQLite demo without replacing uploads.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

if (! $app->environment('local') || DB::connection()->getDriverName() !== 'sqlite') {
    throw new RuntimeException('This import is only for the local SQLite demo.');
}

$photos = json_decode(file_get_contents(base_path('docs/recipe-photo-sources.json')), true, flags: JSON_THROW_ON_ERROR);
foreach ($photos as $photo) {
    if (basename($photo['filename']) !== $photo['filename']) {
        throw new RuntimeException('Invalid photo filename.');
    }
    $file = public_path('images/recipe-samples/'.$photo['filename']);
    $size = is_file($file) ? getimagesize($file) : false;
    if (! $size || $size[0] < 400 || $size[1] < 300 || $size['mime'] !== 'image/jpeg') {
        throw new RuntimeException('Missing or invalid JPEG: '.$photo['filename']);
    }
}

$directory = storage_path('app/public/recipes');
if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
    throw new RuntimeException('Cannot create the recipe image directory.');
}
foreach ($photos as $photo) {
    $destination = $directory.'/'.$photo['filename'];
    if (! file_exists($destination) && ! copy(public_path('images/recipe-samples/'.$photo['filename']), $destination)) {
        throw new RuntimeException('Cannot copy photo: '.$photo['filename']);
    }
}

$backup = base_path('.laravel-bootstrap/preview-before-recipe-photos.sqlite');
if (! is_dir(dirname($backup))) {
    mkdir(dirname($backup), 0777, true);
}
if (! file_exists($backup)) {
    $pdo = DB::connection()->getPdo();
    $pdo->exec('VACUUM INTO '.$pdo->quote($backup));
}

DB::transaction(function () use ($photos): void {
    foreach ($photos as $photo) {
        $path = 'recipes/'.$photo['filename'];
        $recipe = DB::table('recipes')->where('title', $photo['title'])->sole();
        if ($recipe->imageUrl !== null && $recipe->imageUrl !== '/storage/'.$path) {
            echo 'Kept existing image for recipe '.$recipe->id.PHP_EOL;
            continue;
        }
        DB::table('recipes')->where('id', $recipe->id)->update([
            'imageUrl' => '/storage/'.$path,
            'imageId' => $path,
        ]);
        echo 'Photo attached to recipe '.$recipe->id.PHP_EOL;
    }
});

$escape = fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$rows = '';
foreach ($photos as $photo) {
    $license = isset($photo['license'])
        ? ' · <a href="'.$escape($photo['licenseUrl']).'">'.$escape($photo['license']).'</a>' : '';
    $rows .= '<li><strong>'.$escape($photo['title']).'</strong><br><a href="'.$escape($photo['source']).'">'
        .$escape($photo['credit']).'</a>'.$license.'</li>';
}
file_put_contents(public_path('images/recipe-photo-sources.html'), '<!doctype html>
<html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nguồn ảnh công thức · Bếp Nhà</title>
<style>body{max-width:760px;margin:40px auto;padding:0 24px;background:#fffdf7;color:#30241c;font:16px/1.7 "Segoe UI",sans-serif}a{color:#6b3f28}li{margin:20px 0}</style>
<a href="/">← Về Bếp Nhà</a><h1>Nguồn ảnh công thức</h1>
<p>Danh sách nguồn và tác giả ghi trên trang gốc của các ảnh minh họa. Ảnh được tải về máy và hiển thị theo khung ảnh của website.</p>
<ul>'.$rows.'</ul></html>');
