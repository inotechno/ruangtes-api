<?php

namespace App\Http\Requests\AntiCheat;

use App\Http\Requests\BaseRequest;

class LogCheatEventRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detection_type' => ['required', 'string', 'in:tab_switch,window_blur,keyboard_shortcut,right_click,copy_paste,time_anomaly,multiple_devices'],
            'detection_data' => ['nullable', 'array'],
            'severity' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
