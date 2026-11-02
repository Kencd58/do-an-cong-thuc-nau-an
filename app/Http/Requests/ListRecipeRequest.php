<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ListRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        $filters = $this->query();
        if (isset($filters['keyword']) && is_string($filters['keyword'])) {
            $filters['keyword'] = trim($filters['keyword']);
        }
        foreach (['tagIds' => [], 'ingredientIds' => [], 'sort' => 'time', 'order' => 'asc', 'page' => 1] as $key => $default) {
            if (! isset($filters[$key]) || $filters[$key] === '') {
                $filters[$key] = $default;
            }
        }

        return $filters;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'tagIds' => ['array', 'max:10'],
            'tagIds.*' => ['required', 'integer', 'exists:tags,id', 'distinct'],
            'ingredientIds' => ['array', 'max:30'],
            'ingredientIds.*' => ['required', 'integer', 'exists:ingredients,id', 'distinct'],
            'difficulty' => ['nullable', Rule::in(array_keys(config('recipe.difficulties')))],
            'maxTotalTime' => ['nullable', 'integer', 'between:1,2880'],
            'sort' => ['required', Rule::in(['time', 'rating', 'difficulty'])],
            'order' => ['required', Rule::in(['asc', 'desc'])],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route($this->route()->getName())->withErrors($validator, 'filters')
        );
    }

    public function attributes(): array
    {
        return ['keyword' => 'từ khóa', 'categoryId' => 'danh mục', 'tagIds' => 'thẻ', 'tagIds.*' => 'thẻ',
            'ingredientIds' => 'nguyên liệu', 'ingredientIds.*' => 'nguyên liệu', 'difficulty' => 'độ khó',
            'maxTotalTime' => 'tổng thời gian tối đa', 'sort' => 'tiêu chí sắp xếp',
            'order' => 'thứ tự sắp xếp', 'page' => 'trang'];
    }

    public function messages(): array
    {
        return ['page.min' => 'Trang phải lớn hơn hoặc bằng 1.'];
    }
}
