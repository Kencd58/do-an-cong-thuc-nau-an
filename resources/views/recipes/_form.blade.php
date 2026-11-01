@php
    // Old input takes precedence even when every tag or row was removed.
    $hasOld = session()->hasOldInput();
    $formValue = static fn ($value) => is_scalar($value) ? $value : '';
    $ingredientRows = $hasOld ? old('ingredients', []) : ($recipe->exists ? $recipe->ingredients->toArray() : [[]]);
    $stepRows = $hasOld ? old('steps', []) : ($recipe->exists ? $recipe->steps->toArray() : [[]]);
    $selectedTags = $hasOld ? old('tagIds', []) : ($recipe->exists ? $recipe->tags->pluck('id')->all() : []);
    $ingredientRows = is_array($ingredientRows) ? $ingredientRows : [[]];
    $stepRows = is_array($stepRows) ? $stepRows : [[]];
    $selectedTags = is_array($selectedTags) ? $selectedTags : [];
    $localImageUrl = is_string($recipe->imageUrl) && str_starts_with($recipe->imageUrl, '/storage/') ? $recipe->imageUrl : null;
@endphp
<section class="form-section">
    <h2>Thông tin món ăn</h2>
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
<section class="form-section">
    <h2>Nguyên liệu</h2><p class="field-hint">Chọn 1–50 nguyên liệu, mỗi loại một dòng.</p>
    @include('recipes._error', ['field' => 'ingredients'])
    <div data-rows="ingredients" data-max="50">
        @foreach ($ingredientRows as $i => $row) @include('recipes._ingredient', ['row' => is_array($row) ? $row : []]) @endforeach
    </div>
    <button class="nav-button" type="button" data-add="ingredients">+ Thêm nguyên liệu</button>
    <template id="ingredients-template">@include('recipes._ingredient', ['i' => '__INDEX__', 'row' => []])</template>
</section>
<section class="form-section">
    <h2>Cách thực hiện</h2><p class="field-hint">Thêm 1–30 bước theo thứ tự nấu.</p>
    @include('recipes._error', ['field' => 'steps'])
    <div data-rows="steps" data-max="30">
        @foreach ($stepRows as $i => $row) @include('recipes._step', ['row' => is_array($row) ? $row : []]) @endforeach
    </div>
    <button class="nav-button" type="button" data-add="steps">+ Thêm bước</button>
    <template id="steps-template">@include('recipes._step', ['i' => '__INDEX__', 'row' => []])</template>
</section>
<section class="form-section">
    <fieldset class="tag-fieldset"><legend>Thẻ món ăn</legend><p class="field-hint">Không bắt buộc, tối đa 10 thẻ.</p>
        <div class="tag-options">@foreach ($tags as $tag)
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
<div class="form-actions"><button class="button" type="submit">{{ $recipe->exists ? 'Lưu thay đổi' : 'Đăng công thức' }}</button>
    <a href="{{ $recipe->exists ? route('recipes.show', $recipe) : route('recipes.index') }}">Hủy</a>
</div>
