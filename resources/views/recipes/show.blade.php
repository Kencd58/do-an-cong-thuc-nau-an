@extends('layouts.app')
@section('title', $recipe->title)
@section('content')
    @php($recipeImage = is_string($recipe->imageUrl) && str_starts_with($recipe->imageUrl, '/storage/') ? $recipe->imageUrl : asset('images/recipe-default.png'))
    <article class="recipe-detail">
        <a href="{{ route('recipes.index') }}">← Khám phá công thức</a>
        <div class="recipe-hero">
            <div><p class="eyebrow">{{ $recipe->category->name }}</p><h1>{{ $recipe->title }}</h1>
                <p>Bởi {{ $recipe->user->name }}</p><p class="preserve-lines">{{ $recipe->description }}</p>
                <dl class="recipe-facts"><div><dt>Chuẩn bị</dt><dd>{{ $recipe->prepTime }} phút</dd></div><div><dt>Nấu</dt><dd>{{ $recipe->cookTime }} phút</dd></div>
                    <div><dt>Khẩu phần</dt><dd>{{ $recipe->servings }} người</dd></div><div><dt>Độ khó</dt><dd>{{ config('recipe.difficulties')[$recipe->difficulty] ?? $recipe->difficulty }}</dd></div>
                </dl>
                <div class="tags">@foreach ($recipe->tags as $tag)<span>{{ $tag->name }}</span>@endforeach</div>
                @if (auth()->id() !== null && (int) auth()->id() === (int) $recipe->userId)
                    <div class="form-actions"><a class="button" href="{{ route('recipes.edit', $recipe) }}">Sửa công thức</a>
                        <form method="POST" action="{{ route('recipes.destroy', $recipe) }}" data-confirm-delete>@csrf @method('DELETE')<button class="nav-button" type="submit">Xóa công thức</button></form>
                    </div>
                @endif
            </div>
            <img class="recipe-cover" src="{{ $recipeImage }}" data-recipe-image data-fallback="{{ asset('images/recipe-default.png') }}" alt="{{ $recipe->title }}" width="640" height="480">
        </div>
        <div class="recipe-instructions">
            <section><h2>Nguyên liệu</h2><ul class="ingredient-list">@foreach ($recipe->ingredients as $row)
                <li><strong>{{ $row->ingredient->name }}</strong><span>{{ rtrim(rtrim($row->quantity, '0'), '.') }} {{ $row->unit }}</span>@if ($row->note)<p>{{ $row->note }}</p>@endif</li>
            @endforeach</ul></section>
            <section><h2>Cách thực hiện</h2><ol class="step-list">@foreach ($recipe->steps as $step)<li><p class="preserve-lines">{{ $step->instruction }}</p></li>@endforeach</ol></section>
        </div>
        @include('recipes._rating')
        @include('recipes._comments')
    </article>
@endsection
