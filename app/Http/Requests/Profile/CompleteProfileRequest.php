<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CompleteProfileRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:500'],
            'biodata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'address.required' => 'Address is required.',
        ];
    }
}
