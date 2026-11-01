<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRecipeRequest extends FormRequest
{
    public const INPUT_FIELDS = ['title', 'description', 'categoryId', 'prepTime', 'cookTime', 'servings', 'difficulty', 'ingredients', 'steps', 'tagIds', 'removeImage'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tagIds' => $this->input('tagIds', [])]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'prepTime' => ['required', 'integer', 'between:0,1440'],
            'cookTime' => ['required', 'integer', 'between:0,1440'],
            'servings' => ['required', 'integer', 'between:1,100'],
            'difficulty' => ['required', Rule::in(array_keys(config('recipe.difficulties')))],
            'ingredients' => ['required', 'array', 'min:1', 'max:50'],
            'ingredients.*' => ['required', 'array:ingredientId,quantity,unit,note'],
            'ingredients.*.ingredientId' => ['required', 'integer', 'exists:ingredients,id', 'distinct'],
            'ingredients.*.quantity' => ['required', 'numeric', 'between:0.001,99999.999', 'decimal:0,3'],
            'ingredients.*.unit' => ['required', Rule::in(config('recipe.units'))],
            'ingredients.*.note' => ['nullable', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1', 'max:30'],
            'steps.*' => ['required', 'array:instruction'],
            'steps.*.instruction' => ['required', 'string', 'max:2000'],
            'tagIds' => ['array', 'max:10'],
            'tagIds.*' => ['required', 'integer', 'exists:tags,id', 'distinct'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'removeImage' => $this->isMethod('POST') ? ['prohibited'] : ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $validator->errors()->has('prepTime') && ! $validator->errors()->has('cookTime')
                && (int) $this->input('prepTime') + (int) $this->input('cookTime') <= 0) {
                $validator->errors()->add('cookTime', 'Tổng thời gian chuẩn bị và nấu phải lớn hơn 0.');
            }
            if ($this->boolean('removeImage') && $this->hasFile('image')) {
                $validator->errors()->add('image', 'Không thể vừa tải ảnh mới vừa gỡ ảnh hiện tại.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tên công thức', 'description' => 'mô tả', 'categoryId' => 'danh mục',
            'prepTime' => 'thời gian chuẩn bị', 'cookTime' => 'thời gian nấu', 'servings' => 'khẩu phần',
            'difficulty' => 'độ khó', 'ingredients' => 'nguyên liệu', 'steps' => 'các bước',
            'ingredients.*.ingredientId' => 'nguyên liệu', 'ingredients.*.quantity' => 'định lượng',
            'ingredients.*.unit' => 'đơn vị', 'ingredients.*.note' => 'ghi chú',
            'steps.*.instruction' => 'hướng dẫn', 'tagIds' => 'thẻ', 'tagIds.*' => 'thẻ',
            'image' => 'ảnh', 'removeImage' => 'gỡ ảnh',
        ];
    }
}
