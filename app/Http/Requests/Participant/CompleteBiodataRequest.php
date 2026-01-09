<?php

namespace App\Http\Requests\Participant;

use App\Http\Requests\BaseRequest;

class CompleteBiodataRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:500'],
            'additional_info' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
