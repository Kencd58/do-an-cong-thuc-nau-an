@php
    $rowIngredient = isset($row['ingredientId']) ? $ingredients->firstWhere('id', $row['ingredientId']) : null;
    $rowHasErrors = $errors->has("ingredients.$i") || $errors->has("ingredients.$i.*");
    $rowConfirmed = $rowIngredient && !empty($row['quantity']) && !empty($row['unit']) && !$rowHasErrors;
@endphp
<fieldset class="dynamic-row ingredient-row {{ $rowConfirmed ? 'is-confirmed' : 'is-editing' }}" data-row data-confirmed="{{ $rowConfirmed ? 'true' : 'false' }}">
    <legend>Nguyên liệu <span data-number>{{ is_numeric($i) ? $i + 1 : '' }}</span></legend>
    @include('recipes._error', ['field' => "ingredients.$i"])
    <div class="ingredient-editor" data-ingredient-editor @if($rowConfirmed) hidden @endif>
    <div class="ingredient-grid">
        <div class="field"><label for="ingredients-{{ $i }}-ingredientId">Nguyên liệu</label>
            <select data-ingredient id="ingredients-{{ $i }}-ingredientId" name="ingredients[{{ $i }}][ingredientId]" required aria-describedby="ingredients.{{ $i }}.ingredientId-error">
                <option value="">Chọn nguyên liệu</option>
                @foreach ($ingredients as $ingredient)<option value="{{ $ingredient->id }}" data-unit="{{ $ingredient->defaultUnit }}" @selected(($row['ingredientId'] ?? '') == $ingredient->id)>{{ $ingredient->name }}</option>@endforeach
            </select>@include('recipes._error', ['field' => "ingredients.$i.ingredientId"])
        </div>
        <div class="field"><label for="ingredients-{{ $i }}-quantity">Định lượng</label>
            <input id="ingredients-{{ $i }}-quantity" name="ingredients[{{ $i }}][quantity]" type="number" required min="0.001" max="99999.999" step="0.001" value="{{ $formValue($row['quantity'] ?? '') }}" aria-describedby="ingredients.{{ $i }}.quantity-error">
            @include('recipes._error', ['field' => "ingredients.$i.quantity"])
        </div>
        <div class="field"><label for="ingredients-{{ $i }}-unit">Đơn vị</label>
            <select data-unit-select id="ingredients-{{ $i }}-unit" name="ingredients[{{ $i }}][unit]" required aria-describedby="ingredients.{{ $i }}.unit-error"><option value="">Chọn đơn vị</option>
                @foreach (config('recipe.units') as $unit)<option value="{{ $unit }}" @selected(($row['unit'] ?? '') === $unit)>{{ $unit }}</option>@endforeach
            </select>@include('recipes._error', ['field' => "ingredients.$i.unit"])
        </div>
        <div class="field"><label for="ingredients-{{ $i }}-note">Ghi chú (tùy chọn)</label>
            <input id="ingredients-{{ $i }}-note" name="ingredients[{{ $i }}][note]" maxlength="255" value="{{ $formValue($row['note'] ?? '') }}" aria-describedby="ingredients.{{ $i }}.note-error">
            @include('recipes._error', ['field' => "ingredients.$i.note"])
        </div>
    </div>
        <div class="ingredient-editor-actions">
            <button class="nav-button button-compact button-danger-outline ingredient-delete" type="button" data-remove>Xóa nguyên liệu</button>
            <button class="button button-compact" type="button" data-ingredient-confirm>Xác nhận</button>
        </div>
    </div>
    <div class="ingredient-summary" data-ingredient-summary @unless($rowConfirmed) hidden @endunless tabindex="-1">
        <div class="ingredient-summary-content">
            <strong data-summary-name>{{ $rowIngredient?->name }}</strong>
            <span data-summary-amount>{{ $formValue($row['quantity'] ?? '') }} {{ $formValue($row['unit'] ?? '') }}</span>
            <p data-summary-note @if(empty($row['note'])) hidden @endif>{{ $formValue($row['note'] ?? '') }}</p>
        </div>
        <div class="ingredient-summary-actions">
            <button class="nav-button button-compact" type="button" data-ingredient-edit aria-expanded="false">Sửa</button>
            <button class="nav-button button-compact button-danger-outline ingredient-delete" type="button" data-remove>Xóa</button>
        </div>
    </div>
</fieldset>
