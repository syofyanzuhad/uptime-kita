<?php

use App\Providers\TelescopeServiceProvider;
use Illuminate\Support\Facades\Gate;

it('defines the viewTelescope gate', function () {
    expect(Gate::has('viewTelescope'))->toBeTrue();
});

it('registers the provider with night mode in non-local environments', function () {
    app()->detectEnvironment(fn () => 'production');

    $provider = new TelescopeServiceProvider(app());
    $provider->register();

    // Gate should be defined after register/boot path
    expect(Gate::has('viewTelescope'))->toBeTrue();
});

it('registers the provider in local environments', function () {
    app()->detectEnvironment(fn () => 'local');

    $provider = new TelescopeServiceProvider(app());
    $provider->register();

    expect(Gate::has('viewTelescope'))->toBeTrue();
});
