@php
    // Old input takes precedence even when every tag or row was removed.
    $hasOld = session()->hasOldInput();
    $formValue = static fn ($value) => is_scalar($value) ? $value : '';
    $ingredientRows = $hasOld ? old('ingredients', []) : ($recipe->exists ? $recipe->ingredients->toArray() : []);
    $stepRows = $hasOld ? old('steps', []) : ($recipe->exists ? $recipe->steps->toArray() : [[]]);
    $selectedTags = $hasOld ? old('tagIds', []) : ($recipe->exists ? $recipe->tags->pluck('id')->all() : []);
    $ingredientRows = is_array($ingredientRows) ? $ingredientRows : [];
    $stepRows = is_array($stepRows) ? $stepRows : [[]];
    $selectedTags = is_array($selectedTags) ? $selectedTags : [];
    $localImageUrl = is_string($recipe->imageUrl) && str_starts_with($recipe->imageUrl, '/storage/') ? $recipe->imageUrl : null;
@endphp
<section class="form-section" data-form-section="basic">
    <div class="section-heading"><div><h2>Thông tin món ăn</h2><p>Giúp mọi người hình dung món ăn trước khi bắt đầu nấu.</p></div><span class="section-error-badge" data-section-error hidden></span></div>
    <div class="field">
        <label for="title">Tên công thức</label>
        <input id="title" name="title" value="{{ $formValue(old('title', $recipe->title)) }}" required minlength="3" maxlength="150" aria-describedby="title-error" @error('title') aria-invalid="true" @enderror>
        @include('recipes._error', ['field' => 'title'])
    </div>
    <div class="field">
        <label for="description">Mô tả</label>
        <textarea id="description" name="description" required maxlength="2000" rows="4" aria-describedby="description-error">{{ $formValue(old('description', $recipe->description)) }}</textarea>
        @include('recipes._error', ['field' => 'description'])
    </div>
    <div class="form-grid">
        <div class="field"><label for="categoryId">Danh mục</label>
            <select id="categoryId" name="categoryId" required aria-describedby="categoryId-error"><option value="">Chọn danh mục</option>
                @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('categoryId', $recipe->categoryId) == $category->id)>{{ $category->name }}</option>@endforeach
            </select>@include('recipes._error', ['field' => 'categoryId'])
        </div>
        <div class="field"><label for="difficulty">Độ khó</label>
            <select id="difficulty" name="difficulty" required aria-describedby="difficulty-error">
                @foreach (config('recipe.difficulties') as $value => $label)<option value="{{ $value }}" @selected(old('difficulty', $recipe->difficulty ?? 'easy') === $value)>{{ $label }}</option>@endforeach
            </select>@include('recipes._error', ['field' => 'difficulty'])
        </div>
        @foreach (['prepTime' => ['Chuẩn bị (phút)', 0, 1440], 'cookTime' => ['Nấu (phút)', 0, 1440], 'servings' => ['Khẩu phần (người)', 1, 100]] as $field => [$label, $min, $max])
            <div class="field"><label for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="number" min="{{ $min }}" max="{{ $max }}" required value="{{ $formValue(old($field, $recipe->$field ?? ($field === 'servings' ? 2 : 0))) }}" aria-describedby="{{ $field }}-error">
                @include('recipes._error', ['field' => $field])
            </div>
        @endforeach
    </div>
</section>
<section class="form-section" data-form-section="ingredients">
    <div class="section-heading"><div><h2>Nguyên liệu</h2><p>Tìm, chọn rồi thêm từng nguyên liệu vào danh sách.</p></div><span class="section-error-badge" data-section-error hidden></span></div>
    @include('recipes._error', ['field' => 'ingredients'])
    <div class="ingredient-picker" data-ingredient-picker>
        <label for="ingredient-search">Tìm và thêm nguyên liệu</label>
        <div class="ingredient-search-wrap">
            <svg class="ingredient-search-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
            <input id="ingredient-search" type="search" autocomplete="off" placeholder="Ví dụ: trứng, cà chua…" data-ingredient-search
                role="combobox" aria-autocomplete="list" aria-controls="ingredient-picker-panel" aria-expanded="false">
            <button class="ingredient-suggestions-close" type="button" data-ingredient-suggestions-close aria-label="Đóng gợi ý nguyên liệu" hidden>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="ingredient-picker-panel" id="ingredient-picker-panel" data-ingredient-picker-panel hidden>
            <div class="ingredient-results" data-ingredient-results aria-live="polite">
                @foreach ($ingredients as $ingredient)
                    <div class="ingredient-result" data-ingredient-option data-search="{{ mb_strtolower($ingredient->name) }}" data-id="{{ $ingredient->id }}">
                        <span><strong>{{ $ingredient->name }}</strong><small>Đơn vị gợi ý: {{ $ingredient->defaultUnit }}</small></span>
                        <button class="nav-button button-compact" type="button" data-ingredient-add data-id="{{ $ingredient->id }}" data-unit="{{ $ingredient->defaultUnit }}">Thêm</button>
                    </div>
                @endforeach
                <p class="ingredient-no-results" data-ingredient-empty hidden>Không tìm thấy nguyên liệu phù hợp.</p>
            </div>
        </div>
        <p class="ingredient-feedback" data-ingredient-feedback role="status" aria-live="polite"></p>
    </div>
    <div class="selected-ingredients-heading"><h3>Nguyên liệu đã chọn</h3><span data-ingredient-count>{{ count($ingredientRows) }}</span></div>
    <p class="selected-empty" data-selected-empty @if(count($ingredientRows)) hidden @endif>Chưa có nguyên liệu. Hãy tìm và nhấn “Thêm”.</p>
    <div class="selected-ingredients" data-rows="ingredients" data-max="50">
        @foreach ($ingredientRows as $i => $row) @include('recipes._ingredient', ['row' => is_array($row) ? $row : []]) @endforeach
    </div>
    <template id="ingredients-template">@include('recipes._ingredient', ['i' => '__INDEX__', 'row' => []])</template>
</section>
<section class="form-section" data-form-section="steps">
    <div class="section-heading"><div><h2>Cách thực hiện</h2><p>Chia nhỏ hướng dẫn để người nấu dễ làm theo.</p></div><span class="section-error-badge" data-section-error hidden></span></div>
    @include('recipes._error', ['field' => 'steps'])
    <div data-rows="steps" data-max="30">
        @foreach ($stepRows as $i => $row) @include('recipes._step', ['row' => is_array($row) ? $row : []]) @endforeach
    </div>
    <button class="nav-button" type="button" data-add="steps">+ Thêm bước</button>
    <template id="steps-template">@include('recipes._step', ['i' => '__INDEX__', 'row' => []])</template>
</section>
<section class="form-section" data-form-section="extras">
    <div class="section-heading"><div><h2>Hoàn thiện công thức</h2><p>Thêm thẻ và hình ảnh để công thức dễ nhận biết hơn.</p></div><span class="section-error-badge" data-section-error hidden></span></div>
    <fieldset class="tag-fieldset"><legend>Thẻ món ăn</legend><p class="field-hint">Không bắt buộc, tối đa 10 thẻ.</p>
        <div class="tag-options">@foreach ($tags->reject(fn ($tag) => in_array($tag->name, config('recipe.hidden_tags'), true)) as $tag)
            <label class="check-label"><input type="checkbox" name="tagIds[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $selectedTags))> {{ $tag->name }}</label>
        @endforeach</div>
        @foreach ($errors->get('tagIds.*') as $messages) @foreach ($messages as $message)<p class="field-error">{{ $message }}</p>@endforeach @endforeach
        @include('recipes._error', ['field' => 'tagIds'])
    </fieldset>
    <div class="field image-field"><label for="image">Ảnh món ăn (tùy chọn)</label>
        @if ($localImageUrl)<img class="image-preview" src="{{ $localImageUrl }}" data-recipe-image data-fallback="{{ asset('images/recipe-default.png') }}" alt="Ảnh món ăn hiện tại">@endif
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="image-hint image-error">
        <p class="field-hint" id="image-hint">JPEG, PNG hoặc WebP, tối đa 2 MB. Không chọn ảnh mới sẽ giữ ảnh hiện tại.</p>
        @if ($hasOld)<p class="field-hint">Nếu muốn tải ảnh lên, vui lòng chọn lại file ảnh.</p>@endif
        @include('recipes._error', ['field' => 'image'])
        @if ($recipe->exists)
            <label class="check-label"><input type="checkbox" name="removeImage" value="1" @checked(old('removeImage', false))> Gỡ ảnh hiện tại</label>
            @include('recipes._error', ['field' => 'removeImage'])
        @endif
    </div>
</section>
<div class="form-actions recipe-submit-actions">
    <button class="nav-button button-danger-outline" type="button" data-form-cancel data-href="{{ $recipe->exists ? route('recipes.show', $recipe) : route('recipes.index') }}">Hủy</button>
    <button class="button" type="submit">{{ $recipe->exists ? 'Lưu thay đổi' : 'Đăng công thức' }}</button>
</div>
