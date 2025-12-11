<?php

declare(strict_types=1);

use App\Exceptions\OpenRouterException;
use App\Jobs\AnalyzeDamageReportJob;
use App\Models\DamageReport;
use App\Services\OpenRouterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.openrouter.api_key' => 'test-api-key',
        'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
        'services.openrouter.model' => 'anthropic/claude-sonnet-4',
    ]);
});

test('job implements ShouldQueue', function () {
    expect(AnalyzeDamageReportJob::class)->toImplement(ShouldQueue::class);
});

test('job has tries property set to 3', function () {
    $report = DamageReport::factory()->create();
    $job = new AnalyzeDamageReportJob($report);

    expect($job->tries)->toBe(3);
});

test('job has exponential backoff', function () {
    $report = DamageReport::factory()->create();
    $job = new AnalyzeDamageReportJob($report);

    expect($job->backoff())->toBe([10, 30, 60]);
});

test('job accepts damage report in constructor', function () {
    $report = DamageReport::factory()->create();
    $job = new AnalyzeDamageReportJob($report);

    expect($job->damageReport->id)->toBe($report->id);
});

test('job updates report with ai fields on successful analysis', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/photo.jpg', 'fake-image-content');

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

    $report = DamageReport::factory()->submitted()->create([
        'photo_path' => 'damage-reports/1/photo.jpg',
    ]);

    expect($report->ai_severity)->toBeNull()
        ->and($report->ai_damage_type)->toBeNull()
        ->and($report->ai_value_impact)->toBeNull()
        ->and($report->ai_liability)->toBeNull();

    $job = new AnalyzeDamageReportJob($report);
    $job->handle(app(OpenRouterService::class));

    $report->refresh();

    expect($report->ai_severity)->toBe('moderate')
        ->and($report->ai_damage_type)->toBe('crushed')
        ->and($report->ai_value_impact)->toBe('medium')
        ->and($report->ai_liability)->toBe('carrier');
});

test('job does not process report without photo path', function () {
    Http::fake();

    $report = DamageReport::factory()->submitted()->create([
        'photo_path' => null,
    ]);

    $job = new AnalyzeDamageReportJob($report);
    $job->handle(app(OpenRouterService::class));

    Http::assertNothingSent();

    $report->refresh();
    expect($report->ai_severity)->toBeNull();
});

test('job throws exception on api failure allowing retry', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/photo.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'error' => ['message' => 'Service unavailable'],
        ], 503),
    ]);

    $report = DamageReport::factory()->submitted()->create([
        'photo_path' => 'damage-reports/1/photo.jpg',
    ]);

    $job = new AnalyzeDamageReportJob($report);

    expect(fn () => $job->handle(app(OpenRouterService::class)))
        ->toThrow(OpenRouterException::class);

    $report->refresh();
    expect($report->ai_severity)->toBeNull();
});

test('job can be dispatched to queue', function () {
    Queue::fake();

    $report = DamageReport::factory()->draft()->create([
        'photo_path' => 'damage-reports/1/photo.jpg',
    ]);

    AnalyzeDamageReportJob::dispatch($report);

    Queue::assertPushed(AnalyzeDamageReportJob::class, function ($job) use ($report) {
        return $job->damageReport->id === $report->id;
    });
});

test('report status remains submitted and ai fields remain null after max retries', function () {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/photo.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'error' => ['message' => 'Service unavailable'],
        ], 503),
    ]);

    $report = DamageReport::factory()->submitted()->create([
        'photo_path' => 'damage-reports/1/photo.jpg',
    ]);

    $job = new AnalyzeDamageReportJob($report);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        try {
            $job->handle(app(OpenRouterService::class));
        } catch (OpenRouterException) {
        }
    }

    $report->refresh();

    expect($report->status->value)->toBe('submitted')
        ->and($report->ai_severity)->toBeNull()
        ->and($report->ai_damage_type)->toBeNull()
        ->and($report->ai_value_impact)->toBeNull()
        ->and($report->ai_liability)->toBeNull();
});

test('job processes different severity levels correctly', function (string $severity) {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/photo.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'severity' => $severity,
                            'damage_type' => 'crushed',
                            'value_impact' => 'medium',
                            'liability' => 'carrier',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $report = DamageReport::factory()->submitted()->create([
        'photo_path' => 'damage-reports/1/photo.jpg',
    ]);

    $job = new AnalyzeDamageReportJob($report);
    $job->handle(app(OpenRouterService::class));

    $report->refresh();
    expect($report->ai_severity)->toBe($severity);
})->with(['minor', 'moderate', 'severe']);

test('job processes different liability assignments correctly', function (string $liability) {
    Storage::fake('public');
    Storage::disk('public')->put('damage-reports/1/photo.jpg', 'fake-image-content');

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'severity' => 'moderate',
                            'damage_type' => 'crushed',
                            'value_impact' => 'medium',
                            'liability' => $liability,
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $report = DamageReport::factory()->submitted()->create([
        'photo_path' => 'damage-reports/1/photo.jpg',
    ]);

    $job = new AnalyzeDamageReportJob($report);
    $job->handle(app(OpenRouterService::class));

    $report->refresh();
    expect($report->ai_liability)->toBe($liability);
})->with(['carrier', 'sender', 'recipient', 'unknown']);
