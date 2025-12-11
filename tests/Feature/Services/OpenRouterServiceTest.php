<?php

declare(strict_types=1);

use App\Exceptions\OpenRouterException;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'services.openrouter.api_key' => 'test-api-key',
        'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
        'services.openrouter.model' => 'anthropic/claude-sonnet-4',
    ]);
});

test('service throws exception when api key is missing', function () {
    config(['services.openrouter.api_key' => null]);

    new OpenRouterService();
})->throws(OpenRouterException::class, 'OpenRouter configuration missing: api_key');

test('service analyzes damage photo successfully', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'severity' => 'moderate',
                            'damage_type' => 'crushed',
                            'value_impact' => 'medium',
                            'liability' => 'carrier',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService();
    $result = $service->analyzeDamagePhoto('damage-reports/1/test.jpg');

    expect($result)->toBe([
        'severity' => 'moderate',
        'damage_type' => 'crushed',
        'value_impact' => 'medium',
        'liability' => 'carrier',
    ]);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-api-key')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
            && $request['model'] === 'anthropic/claude-sonnet-4'
            && isset($request['messages'][0]['content'][0]['type'])
            && $request['messages'][0]['content'][0]['type'] === 'text'
            && isset($request['messages'][0]['content'][1]['type'])
            && $request['messages'][0]['content'][1]['type'] === 'image_url';
    });
});

test('service handles json response wrapped in code blocks', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => "```json\n{\"severity\": \"severe\", \"damage_type\": \"torn\", \"value_impact\": \"high\", \"liability\": \"sender\"}\n```",
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService();
    $result = $service->analyzeDamagePhoto('damage-reports/1/test.jpg');

    expect($result)->toBe([
        'severity' => 'severe',
        'damage_type' => 'torn',
        'value_impact' => 'high',
        'liability' => 'sender',
    ]);
});

test('service throws exception on api error', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'error' => [
                'message' => 'Rate limit exceeded',
            ],
        ], 429),
    ]);

    $service = new OpenRouterService();
    $service->analyzeDamagePhoto('damage-reports/1/test.jpg');
})->throws(OpenRouterException::class, 'OpenRouter API error: Rate limit exceeded');

test('service throws exception when image not found', function () {
    Storage::fake('public');

    $service = new OpenRouterService();
    $service->analyzeDamagePhoto('damage-reports/1/nonexistent.jpg');
})->throws(OpenRouterException::class, 'Image not found at path');

test('service throws exception on empty response', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response(null, 200),
    ]);

    $service = new OpenRouterService();
    $service->analyzeDamagePhoto('damage-reports/1/test.jpg');
})->throws(OpenRouterException::class, 'Empty response');

test('service throws exception when response has no content', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService();
    $service->analyzeDamagePhoto('damage-reports/1/test.jpg');
})->throws(OpenRouterException::class, 'No content in response');

test('service throws exception when response is not valid json', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is not JSON',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService();
    $service->analyzeDamagePhoto('damage-reports/1/test.jpg');
})->throws(OpenRouterException::class, 'Response is not valid JSON');

test('service throws exception when required field is missing', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'severity' => 'moderate',
                            'damage_type' => 'crushed',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService();
    $service->analyzeDamagePhoto('damage-reports/1/test.jpg');
})->throws(OpenRouterException::class, 'Missing required field: value_impact');

test('service converts image to base64 data uri', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/test.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'severity' => 'minor',
                            'damage_type' => 'punctured',
                            'value_impact' => 'low',
                            'liability' => 'unknown',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService();
    $service->analyzeDamagePhoto('damage-reports/1/test.jpg');

    Http::assertSent(function ($request) {
        $imageUrl = $request['messages'][0]['content'][1]['image_url']['url'] ?? '';

        return str_starts_with($imageUrl, 'data:');
    });
});
