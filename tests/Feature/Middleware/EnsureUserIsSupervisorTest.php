<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['auth', 'supervisor'])->get('/test-supervisor-route', fn () => 'OK');
});

it('allows supervisor to access supervisor routes', function () {
    $supervisor = User::factory()->supervisor()->create();

    $response = $this->actingAs($supervisor)->get('/test-supervisor-route');

    $response->assertOk();
    $response->assertSee('OK');
});

it('denies driver access to supervisor routes', function () {
    $driver = User::factory()->driver()->create();

    $response = $this->actingAs($driver)->get('/test-supervisor-route');

    $response->assertForbidden();
});

it('redirects guest to login', function () {
    $response = $this->get('/test-supervisor-route');

    $response->assertRedirect('/login');
});
