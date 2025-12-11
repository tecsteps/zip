<?php

declare(strict_types=1);

use App\Livewire\Driver\EditReport;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('access control', function () {
    test('guest is redirected to login when accessing edit page', function () {
        $report = DamageReport::factory()->draft()->create();

        $this->get(route('driver.reports.edit', $report))
            ->assertRedirect(route('login'));
    });

    test('authenticated driver can access edit page for own draft report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertOk();
    });

    test('driver cannot access edit page for submitted report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $driver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });

    test('driver cannot access edit page for approved report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $driver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });

    test('driver cannot access edit page for another drivers report', function () {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $otherDriver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });

    test('supervisor cannot access edit page', function () {
        $supervisor = User::factory()->supervisor()->create();
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        $this->actingAs($supervisor)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });
});

describe('page display', function () {
    test('edit page displays report package id', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'package_id' => 'PKG-TEST-123',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSee('Edit Damage Report')
            ->assertSee('PKG-TEST-123');
    });

    test('edit page has back button to dashboard', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSee('Back');
    });
});
