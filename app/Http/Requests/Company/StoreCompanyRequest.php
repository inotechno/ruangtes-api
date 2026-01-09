<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:companies,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
