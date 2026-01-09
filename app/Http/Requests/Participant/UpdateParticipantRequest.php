<?php

namespace App\Http\Requests\Participant;

use App\Http\Requests\BaseRequest;

class UpdateParticipantRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
        ];
    }
}
