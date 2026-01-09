<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\BaseRequest;

class AddToCartRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_id' => ['required', 'integer', 'exists:tests,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'test_id.required' => 'Test ID is required.',
            'test_id.integer' => 'Test ID must be an integer.',
            'test_id.exists' => 'Test not found.',
        ];
    }
}
