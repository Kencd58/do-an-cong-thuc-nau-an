<section id="ratings" class="form-section interaction-section" aria-labelledby="ratings-heading">
    <h2 id="ratings-heading">Đánh giá công thức</h2>
    @if ($ratingCount)
        <p><strong>{{ number_format($averageRating, 1) }} / 5</strong> · {{ $ratingCount }} lượt đánh giá</p>
    @else
        <p>Chưa có đánh giá.</p>
    @endif
    @guest
        <p><a href="{{ route('login') }}">Đăng nhập</a> để đánh giá công thức.</p>
    @else
        @if ((int) auth()->id() !== (int) $recipe->userId)
            @php($selectedScore = $errors->rating->any() ? old('score') : $myRating?->score)
            <form method="POST" action="{{ route('recipes.rating.update', $recipe) }}">
                @csrf @method('PUT')
                <div class="field">
                    <label for="rating-score">Điểm đánh giá của bạn</label>
                    <select id="rating-score" name="score" required @if($errors->rating->any()) aria-invalid="true" aria-describedby="rating-error" @endif>
                        <option value="">Chọn số sao</option>
                        @foreach (range(1, 5) as $score)
                            <option value="{{ $score }}" @selected(is_scalar($selectedScore) && (string) $selectedScore === (string) $score)>{{ $score }} sao</option>
                        @endforeach
                    </select>
                    @error('score', 'rating')<p id="rating-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <button class="button" type="submit">{{ $myRating ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}</button>
            </form>
            @if ($myRating)
                <form class="form-actions" method="POST" action="{{ route('recipes.rating.destroy', $recipe) }}">
                    @csrf @method('DELETE')<button class="nav-button" type="submit">Gỡ đánh giá</button>
                </form>
            @endif
        @endif
    @endguest
</section>
