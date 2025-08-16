<?php

namespace App\Http\Controllers;

use App\Models\StatusPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomDomainController extends Controller
{
    /**
     * Update or set a custom domain for a status page.
     */
    public function update(Request $request, StatusPage $statusPage)
    {
        // Ensure user owns this status page
        if ($statusPage->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i',
                Rule::unique('status_pages')->ignore($statusPage->id),
            ],
            'force_https' => 'boolean',
        ]);

        // If domain is being changed, reset verification
        if ($statusPage->custom_domain !== $validated['custom_domain']) {
            $statusPage->custom_domain_verified = false;
            $statusPage->custom_domain_verified_at = null;

            // Generate new verification token if domain is set
            if ($validated['custom_domain']) {
                $statusPage->generateVerificationToken();
            } else {
                $statusPage->custom_domain_verification_token = null;
            }
        }

        $statusPage->update([
            'custom_domain' => $validated['custom_domain'],
            'force_https' => $validated['force_https'] ?? true,
        ]);

        // Clear cache
        cache()->forget('public_status_page_'.$statusPage->path);

        return back()->with('flash', [
            'type' => 'success',
            'message' => $validated['custom_domain']
                ? 'Custom domain updated. Please verify DNS settings.'
                : 'Custom domain removed.',
        ]);
    }

    /**
     * Verify custom domain DNS settings.
     */
    public function verify(StatusPage $statusPage)
    {
        // Ensure user owns this status page
        if ($statusPage->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (! $statusPage->custom_domain) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'No custom domain configured.',
            ]);
        }

        $verified = $statusPage->verifyCustomDomain();

        if ($verified) {
            // Clear cache
            cache()->forget('public_status_page_'.$statusPage->path);

            return back()->with('flash', [
                'type' => 'success',
                'message' => 'Domain verified successfully!',
            ]);
        }

        return back()->with('flash', [
            'type' => 'error',
            'message' => 'Domain verification failed. Please check your DNS settings.',
        ]);
    }

    /**
     * Get DNS instructions for domain verification.
     */
    public function dnsInstructions(StatusPage $statusPage)
    {
        // Ensure user owns this status page
        if ($statusPage->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (! $statusPage->custom_domain) {
            return response()->json([
                'error' => 'No custom domain configured.',
            ], 400);
        }

        // Generate token if not exists
        if (! $statusPage->custom_domain_verification_token) {
            $statusPage->generateVerificationToken();
        }

        return response()->json([
            'domain' => $statusPage->custom_domain,
            'verification_token' => $statusPage->custom_domain_verification_token,
            'dns_records' => [
                [
                    'type' => 'TXT',
                    'name' => '_uptime-kita.'.$statusPage->custom_domain,
                    'value' => $statusPage->custom_domain_verification_token,
                    'ttl' => 3600,
                ],
                [
                    'type' => 'CNAME',
                    'name' => $statusPage->custom_domain,
                    'value' => parse_url(config('app.url'), PHP_URL_HOST),
                    'ttl' => 3600,
                    'note' => 'Point your domain to our servers',
                ],
            ],
        ]);
    }
}
