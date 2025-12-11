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

    expect($user->role)->toBeInstanceOf(UserRole::class);
    expect($user->role)->toBe(UserRole::Driver);
});
