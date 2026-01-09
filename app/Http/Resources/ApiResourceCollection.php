<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResourceCollection extends ResourceCollection
{
    protected $resourceClass;

    public function __construct($resource, $resourceClass)
    {
        parent::__construct($resource);
        $this->resourceClass = $resourceClass;
    }

    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($item) {
                return new $this->resourceClass($item);
            }),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}

