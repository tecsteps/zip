<?php

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('damage report belongs to user', function () {
    $user = User::factory()->driver()->create();
    $report = DamageReport::factory()->create(['user_id' => $user->id]);

    expect($report->user)->toBeInstanceOf(User::class)
        ->and($report->user->id)->toBe($user->id);
});

test('damage report has approver relationship', function () {
    $report = DamageReport::factory()->approved()->create();

    expect($report->approver)->toBeInstanceOf(User::class)
        ->and($report->approver->role)->toBe(UserRole::Supervisor);
});

test('damage report approver is null when not approved', function () {
    $report = DamageReport::factory()->draft()->create();

    expect($report->approver)->toBeNull();
});

test('forDriver scope filters reports correctly', function () {
    $driver = User::factory()->driver()->create();
    $otherDriver = User::factory()->driver()->create();

    DamageReport::factory()->count(3)->create(['user_id' => $driver->id]);
    DamageReport::factory()->count(2)->create(['user_id' => $otherDriver->id]);

    $reports = DamageReport::forDriver($driver)->get();

    expect($reports)->toHaveCount(3)
        ->and($reports->every(fn ($r) => $r->user_id === $driver->id))->toBeTrue();
});

test('status casts to ReportStatus enum', function () {
    $report = DamageReport::factory()->draft()->create();

    expect($report->status)->toBeInstanceOf(ReportStatus::class)
        ->and($report->status)->toBe(ReportStatus::Draft);
});

test('status submitted casts correctly', function () {
    $report = DamageReport::factory()->submitted()->create();

    expect($report->status)->toBe(ReportStatus::Submitted)
        ->and($report->submitted_at)->not->toBeNull();
});

test('status approved casts correctly', function () {
    $report = DamageReport::factory()->approved()->create();

    expect($report->status)->toBe(ReportStatus::Approved)
        ->and($report->approved_at)->not->toBeNull()
        ->and($report->approved_by)->not->toBeNull();
});

test('user has damage reports relationship', function () {
    $user = User::factory()->driver()->create();
    DamageReport::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->damageReports)->toHaveCount(3);
});

test('factory with ai assessment state works', function () {
    $report = DamageReport::factory()->withAiAssessment()->create();

    expect($report->ai_severity)->not->toBeNull()
        ->and($report->ai_damage_type)->not->toBeNull()
        ->and($report->ai_value_impact)->not->toBeNull()
        ->and($report->ai_liability)->not->toBeNull();
});
