<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    // Ensure Telescope doesn't interfere with tests
    if (class_exists(\Laravel\Telescope\Telescope::class)) {
        \Laravel\Telescope\Telescope::stopRecording();
    }
});

test('database settings page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/database');

    $response->assertOk();
});

test('database settings page includes essential table info', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/database');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Database')
        ->has('essentialTables')
        ->has('excludedTables')
        ->has('essentialRecordCount')
    );
});

test('database settings page requires authentication', function () {
    $response = $this->get('/settings/database');

    $response->assertRedirect('/login');
});

test('database download returns sql file', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/database/download');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/sql');
    $response->assertHeader('content-disposition');
    expect($response->headers->get('content-disposition'))->toContain('.sql');
});

test('database download requires authentication', function () {
    $response = $this->get('/settings/database/download');

    $response->assertRedirect('/login');
});

test('database restore requires authentication', function () {
    $response = $this->post('/settings/database/restore');

    $response->assertRedirect('/login');
});

test('database restore requires a file', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/settings/database')
        ->post('/settings/database/restore', []);

    $response->assertSessionHasErrors('database');
    $response->assertRedirect('/settings/database');
});

test('database restore validates file size', function () {
    $user = User::factory()->create();

    // Create a fake file that exceeds the max size (500MB)
    $file = UploadedFile::fake()->create('backup.sqlite', 512001);

    $response = $this
        ->actingAs($user)
        ->from('/settings/database')
        ->post('/settings/database/restore', [
            'database' => $file,
        ]);

    $response->assertSessionHasErrors('database');
    $response->assertRedirect('/settings/database');
});

test('database restore accepts sql files', function () {
    $user = User::factory()->create();

    // Create a valid SQL backup file
    $sqlContent = "-- Uptime Kita Database Backup\nPRAGMA foreign_keys = OFF;\nPRAGMA foreign_keys = ON;\n";
    $file = UploadedFile::fake()->createWithContent('backup.sql', $sqlContent);

    $response = $this
        ->actingAs($user)
        ->from('/settings/database')
        ->post('/settings/database/restore', [
            'database' => $file,
        ]);

    // Should not have validation errors for the file type
    $response->assertSessionDoesntHaveErrors('database');
});

test('database restore rejects invalid file types', function () {
    $user = User::factory()->create();

    // Create a file with invalid extension
    $file = UploadedFile::fake()->createWithContent('backup.txt', 'invalid content');

    $response = $this
        ->actingAs($user)
        ->from('/settings/database')
        ->post('/settings/database/restore', [
            'database' => $file,
        ]);

    $response->assertSessionHasErrors('database');
});
