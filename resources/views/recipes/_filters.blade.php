@if ($errors->filters->any())
    <div class="notice notice-error" role="alert">
        <strong>Bộ lọc chưa hợp lệ. Vui lòng chọn lại.</strong>
        <ul>@foreach ($errors->filters->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<form method="GET" action="{{ route(request()->route()->getName()) }}" class="form-section filter-panel" aria-label="Bộ lọc công thức">
    <div class="filter-heading">
        <div><h2>Bộ lọc</h2><p>Thu hẹp danh sách theo nguyên liệu và thời gian bạn có.</p></div>
        <a href="{{ route(request()->route()->getName()) }}">Đặt lại</a>
    </div>
    <div class="form-grid">
        <div class="field">
            <label for="keyword">Từ khóa trong tên công thức</label>
            <input type="search" id="keyword" name="keyword" maxlength="100" value="{{ $filters['keyword'] ?? '' }}" placeholder="Ví dụ: trứng">
        </div>
        <div class="field">
            <label for="categoryId">Danh mục</label>
            <select id="categoryId" name="categoryId">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['categoryId'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="difficulty">Độ khó</label>
            <select id="difficulty" name="difficulty">
                <option value="">Tất cả độ khó</option>
                @foreach ($difficulties as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['difficulty'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="maxTotalTime">Tổng thời gian tối đa (phút)</label>
            <input type="number" id="maxTotalTime" name="maxTotalTime" min="1" max="2880" step="1" value="{{ $filters['maxTotalTime'] ?? '' }}">
        </div>
    </div>
    @foreach (['tagIds' => ['Thẻ', $tags->reject(fn ($tag) => in_array($tag->name, config('recipe.hidden_tags'), true))], 'ingredientIds' => ['Nguyên liệu', $ingredients]] as $field => [$label, $options])
        <fieldset class="tag-fieldset">
            <legend>{{ $label }}</legend>
            <p class="field-hint">Có ít nhất một {{ mb_strtolower($label) }} đã chọn.</p>
            <div class="tag-options">
                @foreach ($options as $option)
                    <label class="check-label" for="{{ $field }}-{{ $option->id }}">
                        <input type="checkbox" id="{{ $field }}-{{ $option->id }}" name="{{ $field }}[]" value="{{ $option->id }}" @checked(in_array($option->id, $filters[$field]))>
                        {{ $option->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
    <div class="sort-controls" aria-label="Sắp xếp danh sách">
        <div class="field">
            <label for="sort">Sắp xếp theo</label>
            <select id="sort" name="sort" data-auto-submit>
                @foreach (['time' => 'Thời gian chế biến', 'rating' => 'Số sao đánh giá', 'difficulty' => 'Độ khó'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['sort'] ?? 'time') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="order">Thứ tự</label>
            <select id="order" name="order" data-auto-submit>
                <option value="asc" @selected(($filters['order'] ?? 'asc') === 'asc')>Tăng dần</option>
                <option value="desc" @selected(($filters['order'] ?? 'asc') === 'desc')>Giảm dần</option>
            </select>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="button">Áp dụng</button>
        <a href="{{ route(request()->route()->getName()) }}">Xóa bộ lọc</a>
    </div>
</form>
