<fieldset class="dynamic-row" data-row>
    <legend>Bước <span data-number>{{ is_numeric($i) ? $i + 1 : '' }}</span></legend>
    @include('recipes._error', ['field' => "steps.$i"])
    <div class="field"><label for="steps-{{ $i }}-instruction">Hướng dẫn</label>
        <textarea id="steps-{{ $i }}-instruction" name="steps[{{ $i }}][instruction]" required maxlength="2000" rows="3" aria-describedby="steps.{{ $i }}.instruction-error">{{ $formValue($row['instruction'] ?? '') }}</textarea>
        @include('recipes._error', ['field' => "steps.$i.instruction"])
    </div>
    <button class="remove-row" type="button" data-remove>Bỏ bước</button>
</fieldset>
