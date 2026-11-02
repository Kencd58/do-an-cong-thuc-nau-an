<section id="ratings" class="form-section interaction-section" aria-labelledby="ratings-heading">
    <h2 id="ratings-heading">Đánh giá của bạn</h2>
    @guest
        <p><a href="{{ route('login') }}">Đăng nhập</a> để đánh giá công thức.</p>
    @else
        @if ((int) auth()->id() !== (int) $recipe->userId)
            @php($selectedScore = $errors->rating->any() ? old('score') : $myRating?->score)
            <form method="POST" action="{{ route('recipes.rating.update', $recipe) }}">
                @csrf @method('PUT')
                <div class="field rating-field">
                    <span class="field-label" id="rating-label">Chọn mức đánh giá</span>
                    <div class="star-rating" role="radiogroup" aria-labelledby="rating-label">
                        @foreach (range(5, 1) as $score)
                            <input type="radio" id="rating-score-{{ $score }}" name="score" value="{{ $score }}" required
                                @checked(is_scalar($selectedScore) && (string) $selectedScore === (string) $score)
                                @if($errors->rating->any()) aria-invalid="true" aria-describedby="rating-error" @endif>
                            <label for="rating-score-{{ $score }}"><span class="sr-only">{{ $score }} sao</span>★</label>
                        @endforeach
                    </div>
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
