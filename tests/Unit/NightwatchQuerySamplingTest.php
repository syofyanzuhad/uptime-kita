<?php

use Laravel\Nightwatch\Core;
use Laravel\Nightwatch\QueryConnectionType;
use Laravel\Nightwatch\Records\Query;

it('configures query sample rate and slow query threshold', function () {
    expect(config('nightwatch.sampling.queries'))->not->toBeNull();
    expect(config('nightwatch.sampling.slow_query_threshold_ms'))->not->toBeNull();
});

it('rejects queries based on sample rate while retaining slow queries', function () {
    config()->set('nightwatch.sampling.queries', 0.0);
    config()->set('nightwatch.sampling.slow_query_threshold_ms', 500);

    // Fetch the registered rejectQueryCallbacks via reflection from Core
    $core = app(Core::class);
    $reflection = new ReflectionClass($core);
    $property = $reflection->getProperty('rejectQueryCallbacks');
    $callbacks = $property->getValue($core);

    expect($callbacks)->not->toBeEmpty();

    $callback = end($callbacks);

    // Fast normal query should be rejected under 0.0 sample rate
    $fastQuery = new Query('select * from users where id = 1', 'app/Models/User.php', 10, 10, 'mysql', QueryConnectionType::Direct);
    expect($callback($fastQuery))->toBeTrue();

    // Slow query exceeding 500ms threshold should NOT be rejected
    $slowQuery = new Query('select * from users where id = 1', 'app/Models/User.php', 10, 600, 'mysql', QueryConnectionType::Direct);
    expect($callback($slowQuery))->toBeFalse();
});
