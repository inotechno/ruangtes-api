<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    protected $message;

    protected $statusCode;

    protected $errors;

    public function __construct($message = 'An error occurred', int $statusCode = 400, array $errors = [])
    {
        parent::__construct(null);
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function toArray(Request $request): array
    {
        $response = [
            'success' => false,
            'message' => $this->message,
        ];

        if (! empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode($this->statusCode);
    }

}

