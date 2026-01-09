<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;
use App\Enums\UserRole;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'userable' => ['sometimes', 'required', 'array'],
            'userable.phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'userable.date_of_birth' => ['sometimes', 'nullable', 'date'],
            'userable.address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'userable.biodata' => ['sometimes', 'nullable', 'array'],
            'userable.biodata.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'userable.biodata.email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'userable.biodata.password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'userable.biodata.password_confirmation' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}