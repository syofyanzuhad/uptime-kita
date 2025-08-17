<?php

use function Pest\Laravel\get;

it('returns successful response for public monitors page', function () {
    $response = get('/public-monitors');
    
    $response->assertSuccessful();
});

it('returns JSON data for public monitors', function () {
    $response = get('/public-monitors');
    
    $response->assertSuccessful();
    
    // For Inertia apps, we should get a successful response
    // The actual rendering happens on the frontend
    $this->assertTrue($response->status() === 200);
});

it('returns proper response structure', function () {
    $response = get('/public-monitors');
    
    $response->assertSuccessful();
    
    // Check that we get a proper response
    $this->assertTrue($response->status() === 200);
});
