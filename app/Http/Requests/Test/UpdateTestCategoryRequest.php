<?php

namespace App\Http\Requests\Test;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateTestCategoryRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('test_categories', 'name')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
