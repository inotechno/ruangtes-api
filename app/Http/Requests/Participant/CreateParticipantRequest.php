<?php

namespace App\Http\Requests\Participant;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CreateParticipantRequest extends BaseRequest
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
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['required', 'exists:tests,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}
