<?php

use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('view policy', function () {
    test('owner can view their own report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->create(['user_id' => $owner->id]);

        expect($owner->can('view', $report))->toBeTrue();
    });

    test('other driver cannot view report', function () {
        $owner = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->create(['user_id' => $owner->id]);

        expect($otherDriver->can('view', $report))->toBeFalse();
    });

    test('owner can view submitted report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $owner->id]);

        expect($owner->can('view', $report))->toBeTrue();
    });

    test('owner can view approved report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $owner->id]);

        expect($owner->can('view', $report))->toBeTrue();
    });
});

describe('update policy', function () {
    test('owner can update draft report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $owner->id]);

        expect($owner->can('update', $report))->toBeTrue();
    });

    test('owner cannot update submitted report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $owner->id]);

        expect($owner->can('update', $report))->toBeFalse();
    });

    test('owner cannot update approved report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $owner->id]);

        expect($owner->can('update', $report))->toBeFalse();
    });

    test('other driver cannot update draft report', function () {
        $owner = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $owner->id]);

        expect($otherDriver->can('update', $report))->toBeFalse();
    });
});

describe('delete policy', function () {
    test('owner can delete draft report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $owner->id]);

        expect($owner->can('delete', $report))->toBeTrue();
    });

    test('owner cannot delete submitted report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $owner->id]);

        expect($owner->can('delete', $report))->toBeFalse();
    });

    test('owner cannot delete approved report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $owner->id]);

        expect($owner->can('delete', $report))->toBeFalse();
    });

    test('other driver cannot delete draft report', function () {
        $owner = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $owner->id]);

        expect($otherDriver->can('delete', $report))->toBeFalse();
    });
});

describe('submit policy', function () {
    test('owner can submit draft report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $owner->id]);

        expect($owner->can('submit', $report))->toBeTrue();
    });

    test('owner cannot submit submitted report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $owner->id]);

        expect($owner->can('submit', $report))->toBeFalse();
    });

    test('owner cannot submit approved report', function () {
        $owner = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $owner->id]);

        expect($owner->can('submit', $report))->toBeFalse();
    });

    test('other driver cannot submit draft report', function () {
        $owner = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $owner->id]);

        expect($otherDriver->can('submit', $report))->toBeFalse();
    });
});

describe('supervisor policy', function () {
    test('supervisor cannot view other driver report by default', function () {
        $driver = User::factory()->driver()->create();
        $supervisor = User::factory()->supervisor()->create();
        $report = DamageReport::factory()->create(['user_id' => $driver->id]);

        expect($supervisor->can('view', $report))->toBeFalse();
    });

    test('supervisor cannot update other driver draft report', function () {
        $driver = User::factory()->driver()->create();
        $supervisor = User::factory()->supervisor()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        expect($supervisor->can('update', $report))->toBeFalse();
    });

    test('supervisor cannot delete other driver draft report', function () {
        $driver = User::factory()->driver()->create();
        $supervisor = User::factory()->supervisor()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        expect($supervisor->can('delete', $report))->toBeFalse();
    });

    test('supervisor cannot submit other driver draft report', function () {
        $driver = User::factory()->driver()->create();
        $supervisor = User::factory()->supervisor()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        expect($supervisor->can('submit', $report))->toBeFalse();
    });
});
