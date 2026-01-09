<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'address' => ['sometimes', 'string', 'max:500'],
            'biodata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name must not exceed 255 characters.',
            'phone.string' => 'Phone must be a string.',
            'phone.max' => 'Phone must not exceed 20 characters.',
            'date_of_birth.date' => 'Date of birth must be a valid date.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'address.string' => 'Address must be a string.',
            'address.max' => 'Address must not exceed 500 characters.',
        ];
    }
}
