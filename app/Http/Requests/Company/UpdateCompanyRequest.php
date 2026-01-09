<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('companies')->ignore($this->route('company'))],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
