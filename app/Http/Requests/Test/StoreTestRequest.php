<?php

namespace App\Http\Requests\Test;

use App\Enums\TestType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreTestRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:test_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:tests,code'],
            'price' => ['required', 'numeric', 'min:0'],
            'question_count' => ['nullable', 'integer', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'type' => ['required', Rule::enum(TestType::class)],
            'description' => ['nullable', 'string'],
            'instruction_route' => ['nullable', 'string', 'max:255'],
            'test_route' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
