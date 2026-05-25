<?php

test('homepage includes SmolLaunch featured badge', function () {
    $publicIndexPath = base_path('resources/js/pages/monitors/PublicIndex.vue');

    $contents = file_get_contents($publicIndexPath);

    expect($contents)
        ->not->toBeFalse()
        ->toContain('https://smollaunch.com')
        ->toContain('https://smollaunch.com/badges/featured.svg');
});

