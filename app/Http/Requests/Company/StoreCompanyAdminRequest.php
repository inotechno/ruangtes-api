<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\BaseRequest;

class StoreCompanyAdminRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_primary' => ['boolean'],
        ];
    }
}
