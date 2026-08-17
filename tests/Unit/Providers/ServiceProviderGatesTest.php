<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('allows the horizon gate for the owner email', function () {
    $user = User::factory()->create(['email' => 'mail@syofyanzuhad.dev']);

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBeTrue();
});

it('denies the horizon gate for other users', function () {
    $user = User::factory()->create(['email' => 'other@example.com']);

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBeFalse();
});

it('allows the telescope gate for the owner email', function () {
    $user = User::factory()->create(['email' => 'mail@syofyanzuhad.dev']);

    expect(Gate::forUser($user)->allows('viewTelescope'))->toBeTrue();
});

it('denies the telescope gate for other users', function () {
    $user = User::factory()->create(['email' => 'other@example.com']);

    expect(Gate::forUser($user)->allows('viewTelescope'))->toBeFalse();
});
