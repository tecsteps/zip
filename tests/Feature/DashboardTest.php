<?php

declare(strict_types=1);

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/dashboard')->assertOk();
});

test('driver sees driver dashboard content', function () {
    $driver = User::factory()->driver()->create();

    $response = $this->actingAs($driver)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('My Reports');
    $response->assertDontSee('All Reports');
});

test('supervisor sees supervisor dashboard content', function () {
    $supervisor = User::factory()->supervisor()->create();

    $response = $this->actingAs($supervisor)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('All Reports');
    $response->assertDontSee('My Reports');
});
