<div class="recipe-grid recipe-feed">
    @forelse ($recipes as $recipe)
        @php($postImage = is_string($recipe->imageUrl) && str_starts_with($recipe->imageUrl, '/storage/') ? $recipe->imageUrl : asset('images/recipe-default.png'))
        @php($visibleTags = $recipe->tags->reject(fn ($tag) => in_array($tag->name, config('recipe.hidden_tags'), true)))
        <article class="recipe-card recipe-post">
            <a class="recipe-post-header" href="{{ route('recipes.show', $recipe) }}">
                <span class="post-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($recipe->user->name, 0, 1)) }}</span>
                <span class="post-author-block">
                    <strong>{{ $recipe->user->name }}</strong>
                    <span class="post-context">
                        @if ($recipe->createdAt)<time datetime="{{ $recipe->createdAt->toIso8601String() }}">{{ $recipe->createdAt->format('d/m/Y') }}</time>@endif
                        <span>{{ $recipe->category->name }}</span>
                    </span>
                </span>
                <span class="post-more" aria-hidden="true">•••</span>
            </a>

            <div class="recipe-card-body">
                <h2><a href="{{ route('recipes.show', $recipe) }}">{{ $recipe->title }}</a></h2>
                <p class="recipe-summary preserve-lines">{{ $recipe->description }}</p>
            </div>

            <a class="post-image-link" href="{{ route('recipes.show', $recipe) }}" aria-label="Xem công thức {{ $recipe->title }}">
                <img src="{{ $postImage }}" data-recipe-image data-fallback="{{ asset('images/recipe-default.png') }}" alt="{{ $recipe->title }}" loading="lazy" width="720" height="450">
            </a>

            <div class="post-details">
                <div class="post-facts" aria-label="Thông tin công thức">
                    <span><strong>{{ $recipe->prepTime + $recipe->cookTime }}</strong> phút</span>
                    <span>{{ config('recipe.difficulties')[$recipe->difficulty] ?? $recipe->difficulty }}</span>
                    <span class="post-rating" aria-label="{{ $recipe->ratings_count ? number_format($recipe->ratings_avg_score, 1).' sao' : 'Chưa có đánh giá' }}">
                        <span aria-hidden="true">★</span>
                        {{ $recipe->ratings_count ? number_format($recipe->ratings_avg_score, 1) : 'Mới' }}
                    </span>
                </div>
                @if ($visibleTags->isNotEmpty())
                    <div class="tags" aria-label="Thẻ công thức">@foreach ($visibleTags as $tag)<span>{{ $tag->name }}</span>@endforeach</div>
                @endif
            </div>

            <div class="post-actions">
                <a href="{{ route('recipes.show', $recipe) }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 12h16M14 6l6 6-6 6"/></svg>
                    Xem công thức
                </a>
            </div>
        </article>
    @empty
        <div class="empty-state">
            @if ($recipes->currentPage() > $recipes->lastPage())
                <h2>Trang này không có công thức</h2>
                <p>Trang bạn chọn vượt quá trang cuối của danh sách.</p>
                <a href="{{ $recipes->url(1) }}">Về trang 1</a>
            @elseif (request()->routeIs('home'))
                <h2>Chưa có công thức nào được chia sẻ</h2>
                <p>Hãy chia sẻ món ngon đầu tiên với cộng đồng Bếp Nhà.</p>
                <a class="button" href="{{ route('recipes.create') }}">Chia sẻ công thức đầu tiên</a>
            @elseif (isset($hasRecipes) && ! $hasRecipes)
                <h2>Bạn chưa đăng công thức nào</h2>
                <a href="{{ route('recipes.create') }}">Đăng công thức đầu tiên</a>
            @else
                <h2>Không tìm thấy công thức phù hợp</h2>
                <a href="{{ route(request()->route()->getName()) }}">Xóa bộ lọc</a>
            @endif
        </div>
    @endforelse
</div>
@if ($recipes->hasPages() && $recipes->isNotEmpty())
    <nav class="pagination" aria-label="Phân trang công thức">
        @if ($recipes->previousPageUrl())<a class="nav-button" rel="prev" href="{{ $recipes->previousPageUrl() }}">Trang trước</a>@endif
        <span>Trang {{ $recipes->currentPage() }} / {{ $recipes->lastPage() }}</span>
        @if ($recipes->nextPageUrl())<a class="nav-button" rel="next" href="{{ $recipes->nextPageUrl() }}">Trang sau</a>@endif
    </nav>
@endif
