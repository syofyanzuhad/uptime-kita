<?php

use App\Services\DomainExpirationService;
use Illuminate\Support\Facades\Http;
use Iodev\Whois\Modules\Tld\TldInfo;
use Iodev\Whois\Modules\Tld\TldResponse;
use Iodev\Whois\Whois;

use function Pest\Laravel\mock;

function makeWhoisInfo(?int $timestamp): TldInfo
{
    return new TldInfo(
        new TldResponse(['text' => 'fake whois response']),
        ['expirationDate' => $timestamp]
    );
}

it('returns the expiration date from RDAP when available', function () {
    Http::fake([
        'rdap.org/*' => Http::response([
            'events' => [
                ['eventAction' => 'registration', 'eventDate' => '2012-01-01T00:00:00Z'],
                ['eventAction' => 'expiration', 'eventDate' => '2027-08-13T04:00:00Z'],
            ],
        ], 200),
    ]);

    $service = app(DomainExpirationService::class);

    $date = $service->lookupExpirationDate('https://example.com');

    expect($date)->not->toBeNull();
    expect($date->toDateString())->toBe('2027-08-13');
});

it('strips the www prefix and scheme before looking up', function () {
    Http::fake(function ($request) {
        expect($request->url())->toBe('https://rdap.org/domain/example.com');

        return Http::response([
            'events' => [['eventAction' => 'expiration', 'eventDate' => '2027-08-13T04:00:00Z']],
        ], 200);
    });

    $service = app(DomainExpirationService::class);

    expect($service->lookupExpirationDate('www.example.com'))->not->toBeNull();
});

it('falls back to WHOIS when RDAP has no expiration event', function () {
    Http::fake([
        'rdap.org/*' => Http::response([
            'events' => [['eventAction' => 'registration', 'eventDate' => '2020-01-01T00:00:00Z']],
        ], 200),
    ]);

    $whois = mock(Whois::class);
    $whois->shouldReceive('loadDomainInfo')
        ->once()
        ->with('example.co.id')
        ->andReturn(makeWhoisInfo(strtotime('2027-09-01T00:00:00+00:00')));

    $service = new DomainExpirationService($whois);

    $date = $service->lookupExpirationDate('example.co.id');

    expect($date)->not->toBeNull();
    expect($date->toDateString())->toBe('2027-09-01');
});

it('falls back to WHOIS when the RDAP request fails', function () {
    Http::fake([
        'rdap.org/*' => Http::response([], 404),
    ]);

    $whois = mock(Whois::class);
    $whois->shouldReceive('loadDomainInfo')
        ->once()
        ->andReturn(makeWhoisInfo(strtotime('2027-09-01T00:00:00+00:00')));

    $service = new DomainExpirationService($whois);

    expect($service->lookupExpirationDate('example.co.id'))->not->toBeNull();
});

it('returns null when neither RDAP nor WHOIS provide an expiration date', function () {
    Http::fake([
        'rdap.org/*' => Http::response(['events' => []], 200),
    ]);

    $whois = mock(Whois::class);
    $whois->shouldReceive('loadDomainInfo')->once()->andReturnNull();

    $service = new DomainExpirationService($whois);

    expect($service->lookupExpirationDate('example.co.id'))->toBeNull();
});
