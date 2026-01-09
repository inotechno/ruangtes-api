<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResource extends JsonResource
{
    public static function collection($resource)
    {
        return new ApiResourceCollection($resource, static::class);
    }
}

