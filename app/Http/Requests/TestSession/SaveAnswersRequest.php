<?php

namespace App\Http\Requests\TestSession;

use App\Http\Requests\BaseRequest;

class SaveAnswersRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['required'], // Each answer can be string, array, or number
        ];
    }
}
