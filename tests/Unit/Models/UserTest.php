<?php

use App\Enums\UserRole;
use App\Models\User;

it('returns true for isDriver when user has driver role', function () {
    $user = new User(['role' => UserRole::Driver]);

    expect($user->isDriver())->toBeTrue();
});

it('returns false for isDriver when user has supervisor role', function () {
    $user = new User(['role' => UserRole::Supervisor]);

    expect($user->isDriver())->toBeFalse();
});

it('returns true for isSupervisor when user has supervisor role', function () {
    $user = new User(['role' => UserRole::Supervisor]);

    expect($user->isSupervisor())->toBeTrue();
});

it('returns false for isSupervisor when user has driver role', function () {
    $user = new User(['role' => UserRole::Driver]);

    expect($user->isSupervisor())->toBeFalse();
});

it('casts role attribute to UserRole enum', function () {
    $user = new User(['role' => 'driver']);

    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role)->toBe(UserRole::Driver);
});

it('returns initials from single word name', function () {
    $user = new User(['name' => 'John']);

    expect($user->initials())->toBe('J');
});

it('returns initials from two word name', function () {
    $user = new User(['name' => 'John Doe']);

    expect($user->initials())->toBe('JD');
});

it('returns initials from multiple word name taking only first two', function () {
    $user = new User(['name' => 'John Michael Doe']);

    expect($user->initials())->toBe('JM');
});
