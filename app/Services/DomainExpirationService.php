<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Iodev\Whois\Factory as WhoisFactory;
use Iodev\Whois\Modules\Tld\TldInfo;
use Iodev\Whois\Whois;

class DomainExpirationService
{
    /**
     * @param  Whois|null  $whois  Injectable WHOIS client (used by tests)
     */
    public function __construct(protected ?Whois $whois = null) {}

    /**
     * Look up the domain registration expiration date for the given host.
     *
     * Tries RDAP first (free, covers most gTLDs such as .com, .net, .org and .io),
     * then falls back to a WHOIS lookup (covers ccTLDs such as .id).
     */
    public function lookupExpirationDate(string $host): ?Carbon
    {
        $host = $this->normalizeHost($host);

        if ($host === null) {
            return null;
        }

        $expirationDate = $this->lookupViaRdap($host);

        if ($expirationDate !== null) {
            return $expirationDate;
        }

        return $this->lookupViaWhois($host);
    }

    /**
     * Normalize the host so it can be passed to RDAP/WHOIS servers.
     */
    protected function normalizeHost(string $host): ?string
    {
        $host = strtolower(trim($host));

        // Strip the scheme and path if a full URL was passed in
        if (str_contains($host, '://')) {
            $host = (string) parse_url($host, PHP_URL_HOST);
        }

        if ($host === '') {
            return null;
        }

        // WHOIS/RDAP expect the registrable domain, not the www. subdomain
        return (string) preg_replace('/^www\./', '', $host);
    }

    /**
     * Look up the expiration date via the RDAP protocol (rdap.org).
     */
    protected function lookupViaRdap(string $host): ?Carbon
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['Accept' => 'application/rdap+json'])
                ->get("https://rdap.org/domain/{$host}");

            if ($response->failed()) {
                return null;
            }

            foreach ($response->json('events', []) as $event) {
                if (($event['eventAction'] ?? null) === 'expiration' && ! empty($event['eventDate'])) {
                    return Carbon::parse($event['eventDate']);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Domain expiration RDAP lookup failed', [
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Look up the expiration date via a WHOIS query (port 43).
     */
    protected function lookupViaWhois(string $host): ?Carbon
    {
        try {
            // io-developer/php-whois uses implicit-nullable signatures that trigger
            // PHP 8.4 deprecation warnings; suppress them so they don't pollute the logs.
            $previousErrorReporting = error_reporting(E_ALL & ~E_DEPRECATED);

            try {
                $domainInfo = $this->whoisClient()->loadDomainInfo($host);
            } finally {
                error_reporting($previousErrorReporting);
            }

            if ($domainInfo instanceof TldInfo && $domainInfo->expirationDate) {
                return Carbon::createFromTimestamp($domainInfo->expirationDate);
            }
        } catch (\Throwable $e) {
            Log::warning('Domain expiration WHOIS lookup failed', [
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function whoisClient(): Whois
    {
        return $this->whois ??= WhoisFactory::get()->createWhois();
    }
}
