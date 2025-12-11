<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['auth', 'driver'])->get('/test-driver-route', fn () => 'OK');
});

it('allows driver to access driver routes', function () {
    $driver = User::factory()->driver()->create();

    $response = $this->actingAs($driver)->get('/test-driver-route');

    $response->assertOk();
    $response->assertSee('OK');
});

it('denies supervisor access to driver routes', function () {
    $supervisor = User::factory()->supervisor()->create();

    $response = $this->actingAs($supervisor)->get('/test-driver-route');

    $response->assertForbidden();
});

it('redirects guest to login', function () {
    $response = $this->get('/test-driver-route');

    $response->assertRedirect('/login');
});
