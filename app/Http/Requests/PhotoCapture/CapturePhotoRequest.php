<?php

namespace App\Http\Requests\PhotoCapture;

use App\Http\Requests\BaseRequest;

class CapturePhotoRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // 2MB max
        ];
    }
}
