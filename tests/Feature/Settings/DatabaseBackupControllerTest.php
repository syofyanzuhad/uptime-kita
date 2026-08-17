<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Telescope\Telescope;

beforeEach(function () {
    // Ensure Telescope doesn't interfere with tests
    if (class_exists(Telescope::class)) {
        Telescope::stopRecording();
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

test('database download includes essential table data', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/database/download');

    $content = $response->streamedContent();

    expect($content)->toContain('-- Uptime Kita Database Backup');
    expect($content)->toContain('PRAGMA foreign_keys = OFF;');
    expect($content)->toContain('-- Table: users');
    expect($content)->toContain('-- Table: migrations');
    expect($content)->toContain('PRAGMA foreign_keys = ON;');
});

test('database download escapes string values', function () {
    $user = User::factory()->create(['name' => "O'Reilly"]);

    $response = $this
        ->actingAs($user)
        ->get('/settings/database/download');

    $content = $response->streamedContent();

    expect($content)->toContain("O''Reilly");
});

test('database download marks empty tables', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/settings/database/download');

    $content = $response->streamedContent();

    // tags table should be empty
    expect($content)->toContain("-- Table 'tags' is empty");
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

    // Create a fake file that exceeds the max size (512 * 1024 KB = 524288 KB)
    $file = UploadedFile::fake()->create('backup.sqlite', 524289);

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
    // Skip when using in-memory database because restore operations
    // break the RefreshDatabase transaction isolation
    if (config('database.connections.sqlite.database') === ':memory:') {
        $this->markTestSkipped('Database restore requires file-based SQLite database');
    }

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
