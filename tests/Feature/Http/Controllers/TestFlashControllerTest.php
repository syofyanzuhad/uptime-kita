<?php

use App\Models\User;

describe('TestFlashController', function () {
    it('redirects to dashboard with success flash message', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/test-flash');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('flash.message', 'This is a test flash message!');
        $response->assertSessionHas('flash.type', 'success');
    });

    it('works for unauthenticated users', function () {
        $response = $this->get('/test-flash');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('flash.message', 'This is a test flash message!');
        $response->assertSessionHas('flash.type', 'success');
    });

    it('sets flash message with correct structure', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/test-flash');

        $response->assertSessionHasAll([
            'flash.message' => 'This is a test flash message!',
            'flash.type' => 'success',
        ]);
    });

    it('returns redirect response', function () {
        $response = $this->get('/test-flash');

        $response->assertRedirect();
        expect($response->status())->toBe(302);
    });

    it('can be called multiple times', function () {
        $user = User::factory()->create();

        // First call
        $response1 = $this->actingAs($user)->get('/test-flash');
        $response1->assertRedirect(route('dashboard'));
        $response1->assertSessionHas('flash.message', 'This is a test flash message!');

        // Second call (session would be fresh in a real scenario)
        $response2 = $this->actingAs($user)->get('/test-flash');
        $response2->assertRedirect(route('dashboard'));
        $response2->assertSessionHas('flash.message', 'This is a test flash message!');
    });

    it('flash data structure matches expected format', function () {
        $response = $this->get('/test-flash');

        $response->assertSessionHas('flash', [
            'message' => 'This is a test flash message!',
            'type' => 'success',
        ]);
    });
});
