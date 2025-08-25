<?php

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
});

describe('LoginRequest', function () {
    describe('authorization', function () {
        it('is always authorized', function () {
            $request = new LoginRequest();
            
            expect($request->authorize())->toBeTrue();
        });
    });

    describe('validation rules', function () {
        it('requires email and password', function () {
            $request = new LoginRequest();
            $rules = $request->rules();
            
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('password');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['password'])->toContain('required');
            expect($rules['password'])->toContain('string');
        });
    });

    describe('authenticate', function () {
        it('authenticates valid credentials', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            
            expect(Auth::check())->toBeFalse();
            
            $request->authenticate();
            
            expect(Auth::check())->toBeTrue();
            expect(Auth::user()->email)->toBe('test@example.com');
        });

        it('throws validation exception for invalid credentials', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            
            expect(fn() => $request->authenticate())
                ->toThrow(ValidationException::class);
            
            expect(Auth::check())->toBeFalse();
        });

        it('clears rate limiter on successful authentication', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            
            // Simulate some failed attempts
            RateLimiter::hit($request->throttleKey());
            expect(RateLimiter::attempts($request->throttleKey()))->toBe(1);
            
            $request->authenticate();
            
            expect(RateLimiter::attempts($request->throttleKey()))->toBe(0);
        });

        it('increments rate limiter on failed authentication', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            
            expect(RateLimiter::attempts($request->throttleKey()))->toBe(0);
            
            try {
                $request->authenticate();
            } catch (ValidationException $e) {
                // Expected
            }
            
            expect(RateLimiter::attempts($request->throttleKey()))->toBe(1);
        });
    });

    describe('rate limiting', function () {
        it('allows requests under the limit', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            
            // Should not throw an exception
            $request->ensureIsNotRateLimited();
            
            expect(true)->toBeTrue(); // If we get here, no exception was thrown
        });

        it('blocks requests over the limit', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            
            // Hit the rate limiter 5 times (the limit)
            for ($i = 0; $i < 5; $i++) {
                RateLimiter::hit($request->throttleKey());
            }
            
            expect(fn() => $request->ensureIsNotRateLimited())
                ->toThrow(ValidationException::class);
        });

        it('generates correct throttle key', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'Test@Example.COM',
                'password' => 'password',
            ], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
            
            $throttleKey = $request->throttleKey();
            
            expect($throttleKey)->toContain('test@example.com');
            expect($throttleKey)->toContain('192.168.1.1');
            expect($throttleKey)->toContain('|');
        });

        it('normalizes email in throttle key', function () {
            $request1 = LoginRequest::create('/login', 'POST', [
                'email' => 'Test@Example.COM',
            ], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
            
            $request2 = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
            ], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
            
            expect($request1->throttleKey())->toBe($request2->throttleKey());
        });
    });

    describe('remember me functionality', function () {
        it('handles remember me when true', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'password',
                'remember' => true,
            ]);
            
            $request->authenticate();
            
            expect(Auth::check())->toBeTrue();
            // Note: In a real test, you might check for the remember cookie
        });

        it('handles remember me when false', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'password',
                'remember' => false,
            ]);
            
            $request->authenticate();
            
            expect(Auth::check())->toBeTrue();
        });

        it('handles missing remember parameter', function () {
            $request = LoginRequest::create('/login', 'POST', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            
            $request->authenticate();
            
            expect(Auth::check())->toBeTrue();
        });
    });
});