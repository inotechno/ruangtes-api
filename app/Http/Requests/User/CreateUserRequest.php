<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;
use App\Enums\UserRole;
use Illuminate\Validation\Rule;

class CreateUserRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'userable' => ['required', 'array'],
            'userable.phone' => ['nullable', 'string', 'max:20'],
            'userable.date_of_birth' => ['nullable', 'date'],
            'userable.address' => ['nullable', 'string', 'max:255'],
            'userable.biodata' => ['nullable', 'array'],
            'userable.biodata.name' => ['nullable', 'string', 'max:255'],
            'userable.biodata.email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'userable.biodata.password' => ['nullable', 'string', 'min:8'],
            'userable.biodata.password_confirmation' => ['nullable', 'string', 'min:8'],
        ];
    }
}