<?php

namespace App\Http\Controllers;

use App\Services\PublicToolsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicToolsController extends Controller
{
    public function __construct(
        private readonly PublicToolsService $toolsService
    ) {}

    /**
     * Display the Free Tools directory / hub.
     */
    public function index(): Response
    {
        $appUrl = config('app.url');

        $tools = [
            [
                'slug' => 'ssl-checker',
                'title' => 'SSL Certificate Checker',
                'description' => 'Verify SSL certificate validity, issuer, days to expiration, and SAN domains in real time.',
                'icon' => 'lock',
                'color' => 'from-emerald-500 to-teal-600',
                'badge' => 'Instant',
                'href' => route('tools.ssl-checker'),
            ],
            [
                'slug' => 'dns-lookup',
                'title' => 'DNS Record Lookup',
                'description' => 'Inspect A, AAAA, MX, TXT, CNAME, and NS records with TTL values.',
                'icon' => 'globe',
                'color' => 'from-blue-500 to-indigo-600',
                'badge' => 'Comprehensive',
                'href' => route('tools.dns-lookup'),
            ],
            [
                'slug' => 'headers-checker',
                'title' => 'Security Headers Analyzer',
                'description' => 'Audit HSTS, CSP, X-Frame-Options, and security headers with instant grading (A+ to F).',
                'icon' => 'shield',
                'color' => 'from-purple-500 to-violet-600',
                'badge' => 'Security Audit',
                'href' => route('tools.headers-checker'),
            ],
            [
                'slug' => 'badge-generator',
                'title' => 'GitHub README Badge Generator',
                'description' => 'Generate dynamic live uptime status shield badges for your open-source projects.',
                'icon' => 'tag',
                'color' => 'from-amber-500 to-orange-600',
                'badge' => 'Shields',
                'href' => route('tools.badge-generator'),
            ],
        ];

        return Inertia::render('tools/Index', [
            'tools' => $tools,
            'appUrl' => $appUrl,
        ]);
    }

    /**
     * Display the SSL Checker tool page.
     */
    public function sslChecker(Request $request): Response
    {
        $domain = $request->query('domain', '');
        $initialResult = null;

        if (! empty($domain)) {
            $initialResult = $this->toolsService->checkSsl($domain);
        }

        return Inertia::render('tools/SslChecker', [
            'initialDomain' => $domain,
            'initialResult' => $initialResult,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * API for SSL Checker.
     */
    public function apiCheckSsl(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->toolsService->checkSsl($request->input('domain'));

        return response()->json($result);
    }

    /**
     * Display the DNS Lookup tool page.
     */
    public function dnsLookup(Request $request): Response
    {
        $domain = $request->query('domain', '');
        $type = $request->query('type', 'ALL');
        $initialResult = null;

        if (! empty($domain)) {
            $initialResult = $this->toolsService->lookupDns($domain, $type);
        }

        return Inertia::render('tools/DnsLookup', [
            'initialDomain' => $domain,
            'initialType' => $type,
            'initialResult' => $initialResult,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * API for DNS Lookup.
     */
    public function apiLookupDns(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:ALL,A,AAAA,MX,TXT,CNAME,NS,SOA'],
        ]);

        $result = $this->toolsService->lookupDns(
            $request->input('domain'),
            $request->input('type', 'ALL')
        );

        return response()->json($result);
    }

    /**
     * Display the Headers Checker tool page.
     */
    public function headersChecker(Request $request): Response
    {
        $url = $request->query('url', '');
        $initialResult = null;

        if (! empty($url)) {
            $initialResult = $this->toolsService->checkHeaders($url);
        }

        return Inertia::render('tools/HeadersChecker', [
            'initialUrl' => $url,
            'initialResult' => $initialResult,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * API for Headers Checker.
     */
    public function apiCheckHeaders(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->toolsService->checkHeaders($request->input('url'));

        return response()->json($result);
    }

    /**
     * Display the Badge Generator tool page.
     */
    public function badgeGenerator(Request $request): Response
    {
        return Inertia::render('tools/BadgeGenerator', [
            'initialDomain' => $request->query('domain', 'google.com'),
            'appUrl' => config('app.url'),
        ]);
    }
}
