@extends('layouts.app')
@section('title', $recipe->title)
@section('content')
    @php($recipeImage = is_string($recipe->imageUrl) && str_starts_with($recipe->imageUrl, '/storage/') ? $recipe->imageUrl : asset('images/recipe-default.png'))
    <article class="recipe-detail">
        <a class="back-link" href="{{ route('recipes.index') }}" data-back-link>
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m15 6-6 6 6 6"/></svg>
            Quay lại
        </a>
        <div class="recipe-hero">
            <img class="recipe-cover" src="{{ $recipeImage }}" data-recipe-image data-fallback="{{ asset('images/recipe-default.png') }}" alt="{{ $recipe->title }}" width="640" height="480">
            <div class="recipe-overview"><p class="eyebrow">{{ $recipe->category->name }}</p><h1>{{ $recipe->title }}</h1>
                <div class="rating-summary" aria-label="{{ $ratingCount ? number_format($averageRating, 1).' trên 5 sao, '.$ratingCount.' lượt đánh giá' : 'Chưa có đánh giá' }}">
                    <span class="rating-stars" aria-hidden="true" style="--rating: {{ $ratingCount ? round($averageRating * 20) : 0 }}%">★★★★★</span>
                    @if ($ratingCount)
                        <strong>{{ number_format($averageRating, 1) }}</strong><span>/ 5</span><span>({{ $ratingCount }} lượt)</span>
                    @else
                        <span>Chưa có đánh giá</span>
                    @endif
                </div>
                <p>Bởi {{ $recipe->user->name }}</p><p class="preserve-lines">{{ $recipe->description }}</p>
                <dl class="recipe-facts"><div><dt>Chuẩn bị</dt><dd>{{ $recipe->prepTime }} phút</dd></div><div><dt>Nấu</dt><dd>{{ $recipe->cookTime }} phút</dd></div>
                    <div><dt>Khẩu phần</dt><dd>{{ $recipe->servings }} người</dd></div><div><dt>Độ khó</dt><dd>{{ config('recipe.difficulties')[$recipe->difficulty] ?? $recipe->difficulty }}</dd></div>
                </dl>
                <div class="tags">@foreach ($recipe->tags->reject(fn ($tag) => in_array($tag->name, config('recipe.hidden_tags'), true)) as $tag)<span>{{ $tag->name }}</span>@endforeach</div>
                @if (auth()->id() !== null && (int) auth()->id() === (int) $recipe->userId)
                    <div class="form-actions"><a class="button" href="{{ route('recipes.edit', $recipe) }}">Sửa công thức</a>
                        <form method="POST" action="{{ route('recipes.destroy', $recipe) }}" data-confirm-delete>@csrf @method('DELETE')<button class="nav-button" type="submit">Xóa công thức</button></form>
                    </div>
                @endif
            </div>
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
