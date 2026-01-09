<?php

namespace App\Http\Requests\Test;

use App\Enums\TestType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateTestRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $testId = $this->route('test');

        return [
            'category_id' => ['sometimes', 'nullable', 'exists:test_categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('tests', 'code')->ignore($testId)],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'question_count' => ['nullable', 'integer', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'type' => ['sometimes', 'required', Rule::enum(TestType::class)],
            'description' => ['nullable', 'string'],
            'instruction_route' => ['nullable', 'string', 'max:255'],
            'test_route' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
