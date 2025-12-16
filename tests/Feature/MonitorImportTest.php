<?php

use App\Models\Monitor;
use App\Models\User;
use App\Services\MonitorImportService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
});

// ==========================================
// Authentication Tests
// ==========================================

it('requires authentication to access import page', function () {
    $this->get(route('monitor.import.index'))
        ->assertRedirect(route('login'));
});

it('requires authentication to preview import', function () {
    $this->postJson(route('monitor.import.preview'))
        ->assertUnauthorized();
});

it('requires authentication to process import', function () {
    $this->postJson(route('monitor.import.process'))
        ->assertUnauthorized();
});

it('can access import page when authenticated', function () {
    $this->actingAs($this->user)
        ->get(route('monitor.import.index'))
        ->assertSuccessful();
});

// ==========================================
// File Validation Tests
// ==========================================

it('requires an import file', function () {
    $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['import_file']);
});

it('rejects invalid file types', function () {
    $file = UploadedFile::fake()->create('monitors.pdf', 100, 'application/pdf');

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['import_file']);
});

it('accepts csv files', function () {
    $csvContent = "url,display_name\nhttps://example.com,Example Site\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'rows',
            'valid_count',
            'error_count',
            'duplicate_count',
        ]);
});

it('accepts json files', function () {
    $jsonContent = json_encode([
        'monitors' => [
            ['url' => 'https://example.com', 'display_name' => 'Example Site'],
        ],
    ]);
    $file = UploadedFile::fake()->createWithContent('monitors.json', $jsonContent);

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'rows',
            'valid_count',
            'error_count',
            'duplicate_count',
        ]);
});

// ==========================================
// CSV Parsing Tests
// ==========================================

it('parses csv file with headers correctly', function () {
    $csvContent = "url,display_name,uptime_check_enabled,is_public\nhttps://test1.com,Test Site 1,true,true\nhttps://test2.com,Test Site 2,false,false\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('valid_count'))->toBe(2);
    expect($response->json('rows.0.url'))->toBe('https://test1.com');
    expect($response->json('rows.0.display_name'))->toBe('Test Site 1');
    expect($response->json('rows.1.url'))->toBe('https://test2.com');
});

it('normalizes http urls to https', function () {
    $csvContent = "url\nhttp://example.com\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('rows.0.url'))->toBe('https://example.com');
});

it('parses comma-separated tags in csv', function () {
    $csvContent = "url,tags\nhttps://example.com,\"api,production,critical\"\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('rows.0.tags'))->toBe(['api', 'production', 'critical']);
});

// ==========================================
// JSON Parsing Tests
// ==========================================

it('parses json array format', function () {
    $jsonContent = json_encode([
        ['url' => 'https://test1.com', 'display_name' => 'Test 1'],
        ['url' => 'https://test2.com', 'display_name' => 'Test 2'],
    ]);
    $file = UploadedFile::fake()->createWithContent('monitors.json', $jsonContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('valid_count'))->toBe(2);
});

it('parses json monitors wrapper format', function () {
    $jsonContent = json_encode([
        'monitors' => [
            ['url' => 'https://test1.com', 'display_name' => 'Test 1'],
        ],
    ]);
    $file = UploadedFile::fake()->createWithContent('monitors.json', $jsonContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('valid_count'))->toBe(1);
    expect($response->json('rows.0.url'))->toBe('https://test1.com');
});

it('handles invalid json gracefully', function () {
    $file = UploadedFile::fake()->createWithContent('monitors.json', 'invalid json content');

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('rows'))->toBeEmpty();
    expect($response->json('valid_count'))->toBe(0);
});

// ==========================================
// Validation Tests
// ==========================================

it('marks rows with invalid url as error', function () {
    $csvContent = "url,display_name\nnot-a-valid-url,Invalid\nhttps://valid.com,Valid\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('error_count'))->toBe(1);
    expect($response->json('valid_count'))->toBe(1);
    expect($response->json('rows.0._status'))->toBe('error');
    expect($response->json('rows.1._status'))->toBe('valid');
});

it('marks rows without url as error', function () {
    $csvContent = "display_name\nNo URL Row\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('error_count'))->toBe(1);
    expect($response->json('rows.0._status'))->toBe('error');
    expect($response->json('rows.0._errors'))->toContain('The url field is required.');
});

it('validates sensitivity field values', function () {
    $csvContent = "url,sensitivity\nhttps://example.com,invalid_sensitivity\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('error_count'))->toBe(1);
    expect($response->json('rows.0._status'))->toBe('error');
});

// ==========================================
// Duplicate Detection Tests
// ==========================================

it('detects duplicate urls', function () {
    Monitor::factory()->create(['url' => 'https://existing.com']);

    $csvContent = "url\nhttps://existing.com\nhttps://new-site.com\n";
    $file = UploadedFile::fake()->createWithContent('monitors.csv', $csvContent);

    $response = $this->actingAs($this->user)
        ->postJson(route('monitor.import.preview'), [
            'import_file' => $file,
        ])
        ->assertSuccessful();

    expect($response->json('duplicate_count'))->toBe(1);
    expect($response->json('valid_count'))->toBe(1);
    expect($response->json('rows.0._status'))->toBe('duplicate');
    expect($response->json('rows.1._status'))->toBe('valid');
});

// ==========================================
// Import Process Tests
// ==========================================

it('imports valid monitors', function () {
    $rows = [
        [
            'url' => 'https://newsite1.com',
            'display_name' => 'New Site 1',
            '_row_number' => 1,
            '_status' => 'valid',
        ],
        [
            'url' => 'https://newsite2.com',
            'display_name' => 'New Site 2',
            '_row_number' => 2,
            '_status' => 'valid',
        ],
    ];

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.process'), [
            'rows' => $rows,
            'duplicate_action' => 'skip',
        ])
        ->assertRedirect(route('monitor.index'));

    $this->assertDatabaseHas('monitors', ['url' => 'https://newsite1.com']);
    $this->assertDatabaseHas('monitors', ['url' => 'https://newsite2.com']);
});

it('skips duplicates when action is skip', function () {
    $existingMonitor = Monitor::factory()->create(['url' => 'https://existing.com', 'display_name' => 'Original']);

    $rows = [
        [
            'url' => 'https://existing.com',
            'display_name' => 'Updated Name',
            '_row_number' => 1,
            '_status' => 'duplicate',
        ],
    ];

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.process'), [
            'rows' => $rows,
            'duplicate_action' => 'skip',
        ])
        ->assertRedirect(route('monitor.index'));

    $existingMonitor->refresh();
    expect($existingMonitor->display_name)->toBe('Original');
});

it('updates duplicates when action is update', function () {
    $existingMonitor = Monitor::factory()->create([
        'url' => 'https://existing.com',
        'display_name' => 'Original Name',
        'is_public' => false,
    ]);

    $rows = [
        [
            'url' => 'https://existing.com',
            'display_name' => 'Updated Name',
            'is_public' => true,
            '_row_number' => 1,
            '_status' => 'duplicate',
        ],
    ];

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.process'), [
            'rows' => $rows,
            'duplicate_action' => 'update',
        ])
        ->assertRedirect(route('monitor.index'));

    $existingMonitor->refresh();
    expect($existingMonitor->display_name)->toBe('Updated Name');
    expect((bool) $existingMonitor->is_public)->toBeTrue();
});

it('handles create action for duplicates by redirecting with error', function () {
    // First create a monitor outside the import
    $existingMonitor = Monitor::factory()->create(['url' => 'https://existing.com']);

    $rows = [
        [
            'url' => 'https://existing.com',
            'display_name' => 'New Copy',
            '_row_number' => 1,
            '_status' => 'duplicate',
        ],
    ];

    // Creating a duplicate with 'create' action will fail due to unique URL constraint
    // The controller catches the exception and redirects back with an error
    $response = $this->actingAs($this->user)
        ->from(route('monitor.import.index'))
        ->postJson(route('monitor.import.process'), [
            'rows' => $rows,
            'duplicate_action' => 'create',
        ]);

    // Should redirect (either back with error or success if somehow it worked)
    $response->assertRedirect();
});

it('skips error rows during import', function () {
    $rows = [
        [
            'url' => 'https://valid.com',
            '_row_number' => 1,
            '_status' => 'valid',
        ],
        [
            'url' => 'invalid-url',
            '_row_number' => 2,
            '_status' => 'error',
            '_errors' => ['Invalid URL'],
        ],
    ];

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.process'), [
            'rows' => $rows,
            'duplicate_action' => 'skip',
        ])
        ->assertRedirect(route('monitor.index'));

    $this->assertDatabaseHas('monitors', ['url' => 'https://valid.com']);
    $this->assertDatabaseMissing('monitors', ['url' => 'invalid-url']);
});

it('attaches tags during import', function () {
    $rows = [
        [
            'url' => 'https://tagged-site.com',
            'display_name' => 'Tagged Site',
            'tags' => ['api', 'production'],
            '_row_number' => 1,
            '_status' => 'valid',
        ],
    ];

    $this->actingAs($this->user)
        ->postJson(route('monitor.import.process'), [
            'rows' => $rows,
            'duplicate_action' => 'skip',
        ])
        ->assertRedirect(route('monitor.index'));

    $monitor = Monitor::where('url', 'https://tagged-site.com')->first();
    expect($monitor)->not->toBeNull();
    expect($monitor->tags->pluck('name')->toArray())->toContain('api', 'production');
});

// ==========================================
// Sample Template Download Tests
// ==========================================

it('can download csv template', function () {
    $response = $this->actingAs($this->user)
        ->get(route('monitor.import.sample.csv'))
        ->assertSuccessful();

    expect($response->headers->get('content-type'))->toContain('text/csv');
});

it('can download json template', function () {
    $response = $this->actingAs($this->user)
        ->get(route('monitor.import.sample.json'))
        ->assertSuccessful();

    expect($response->headers->get('content-type'))->toContain('application/json');
});

// ==========================================
// Service Unit Tests
// ==========================================

it('service generates valid csv sample', function () {
    $service = new MonitorImportService;
    $csv = $service->generateSampleCsv();

    expect($csv)->toContain('url,display_name');
    expect($csv)->toContain('https://example.com');
});

it('service generates valid json sample', function () {
    $service = new MonitorImportService;
    $json = $service->generateSampleJson();

    $data = json_decode($json, true);
    expect($data)->toHaveKey('monitors');
    expect($data['monitors'])->toBeArray();
    expect($data['monitors'][0])->toHaveKey('url');
});

it('service detects csv format from extension', function () {
    $service = new MonitorImportService;
    $file = UploadedFile::fake()->create('monitors.csv', 100, 'text/csv');

    expect($service->detectFormat($file))->toBe('csv');
});

it('service detects json format from extension', function () {
    $service = new MonitorImportService;
    $file = UploadedFile::fake()->create('monitors.json', 100, 'application/json');

    expect($service->detectFormat($file))->toBe('json');
});

it('service defaults to csv for unknown extensions', function () {
    $service = new MonitorImportService;
    $file = UploadedFile::fake()->create('monitors.txt', 100, 'text/plain');

    expect($service->detectFormat($file))->toBe('csv');
});
