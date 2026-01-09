<?php

namespace App\Http\Requests\Participant;

use App\Http\Requests\BaseRequest;

class ImportParticipantsRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // 5MB max
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['required', 'exists:tests,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}
