<section id="comments" class="form-section interaction-section" aria-labelledby="comments-heading">
    <h2 id="comments-heading">Bình luận ({{ $comments->total() }})</h2>
    @guest
        <p><a href="{{ route('login') }}">Đăng nhập</a> để viết bình luận.</p>
    @else
        @php
            $createContent = $errors->commentCreate->any() ? old('content') : '';
        @endphp
        <form method="POST" action="{{ route('recipes.comments.store', $recipe) }}">
            @csrf
            <input type="hidden" name="commentsPage" value="{{ $comments->currentPage() }}">
            <div class="field">
                <label for="comment-content">Bình luận của bạn</label>
                <textarea id="comment-content" name="content" rows="3" required maxlength="2000" @if($errors->commentCreate->any()) aria-invalid="true" aria-describedby="comment-create-error" @endif>{{ is_string($createContent) ? $createContent : '' }}</textarea>
                @error('content', 'commentCreate')<p id="comment-create-error" class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button class="button" type="submit">Gửi bình luận</button>
        </form>
    @endguest
    <div class="comment-list">
        @forelse ($comments as $comment)
            <article class="comment" id="comment-{{ $comment->id }}">
                <p><strong>{{ $comment->user->name }}</strong>
                    <time datetime="{{ $comment->createdAt?->toIso8601String() }}">{{ $comment->createdAt?->format('d/m/Y H:i') }}</time>
                    @if ($comment->updatedAt && $comment->createdAt && $comment->updatedAt->gt($comment->createdAt))<span> · Đã chỉnh sửa</span>@endif
                </p>
                <p class="preserve-lines">{{ $comment->content }}</p>
                @if (auth()->id() !== null && (int) auth()->id() === $comment->userId)
                    @php
                        $bag = 'commentUpdate_'.$comment->id;
                        $hasErrors = $errors->getBag($bag)->any();
                        $editContent = $hasErrors ? old('content') : $comment->content;
                    @endphp
                    <div class="form-actions">
                        <button class="nav-button" type="button" data-comment-toggle="comment-edit-{{ $comment->id }}" aria-controls="comment-edit-{{ $comment->id }}" aria-expanded="{{ $hasErrors ? 'true' : 'false' }}">Sửa bình luận</button>
                        <form method="POST" action="{{ route('recipes.comments.destroy', [$recipe, $comment]) }}" data-confirm-delete="Xóa bình luận này? Thao tác này không thể hoàn tác.">
                            @csrf @method('DELETE')<button class="nav-button" type="submit">Xóa bình luận</button>
                        </form>
                    </div>
                    <form id="comment-edit-{{ $comment->id }}" method="POST" action="{{ route('recipes.comments.update', [$recipe, $comment]) }}" @if(! $hasErrors) hidden @endif>
                        @csrf @method('PUT')
                        <input type="hidden" name="commentsPage" value="{{ $comments->currentPage() }}">
                        <div class="field">
                            <label for="comment-content-{{ $comment->id }}">Sửa nội dung bình luận</label>
                            <textarea id="comment-content-{{ $comment->id }}" name="content" rows="3" required maxlength="2000" @if($hasErrors) aria-invalid="true" aria-describedby="comment-error-{{ $comment->id }}" @endif>{{ is_string($editContent) ? $editContent : '' }}</textarea>
                            @error('content', $bag)<p id="comment-error-{{ $comment->id }}" class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <button class="button" type="submit">Lưu bình luận</button>
                    </form>
                @endif
            </article>
        @empty
            <p>{{ $comments->currentPage() > 1 ? 'Trang này không có bình luận.' : 'Chưa có bình luận. Hãy chia sẻ trải nghiệm của bạn.' }}</p>
            @if ($comments->currentPage() > 1)<a href="{{ $comments->url(1) }}">Về trang 1</a>@endif
        @endforelse
    </div>
    @if ($comments->hasPages() && $comments->isNotEmpty())
        <nav class="pagination" aria-label="Phân trang bình luận">
            @if ($comments->previousPageUrl())<a class="nav-button" rel="prev" href="{{ $comments->previousPageUrl() }}">Trang trước</a>@endif
            <span>Trang {{ $comments->currentPage() }} / {{ $comments->lastPage() }}</span>
            @if ($comments->nextPageUrl())<a class="nav-button" rel="next" href="{{ $comments->nextPageUrl() }}">Trang sau</a>@endif
        </nav>
    @endif
</section>
