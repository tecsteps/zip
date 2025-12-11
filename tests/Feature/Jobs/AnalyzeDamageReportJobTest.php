<?php

declare(strict_types=1);

use App\Jobs\AnalyzeDamageReportJob;
use App\Models\DamageReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
