<?php

test('the application returns a successful response', function () {
    $response = $this->get('/health');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'timestamp',
        'services' => [
            'database',
            'redis',
        ],
        'version',
    ]);
});
