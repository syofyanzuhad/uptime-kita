<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Str;

class StatusPage extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'icon',
        'path',
        'custom_domain',
        'custom_domain_verified',
        'custom_domain_verification_token',
        'custom_domain_verified_at',
        'force_https',
    ];

    protected $casts = [
        'custom_domain_verified' => 'boolean',
        'force_https' => 'boolean',
        'custom_domain_verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the status page.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the monitors associated with this status page.
     */
    public function monitors()
    {
        return $this->belongsToMany(Monitor::class, 'status_page_monitor');
    }

    /**
     * Generate a unique path for the status page.
     */
    public static function generateUniquePath(string $title): string
    {
        $basePath = Str::slug($title);
        $path = $basePath;
        $counter = 1;

        while (static::where('path', $path)->exists()) {
            $path = $basePath.'-'.$counter;
            $counter++;
        }

        return $path;
    }

    /**
     * Generate a verification token for custom domain.
     */
    public function generateVerificationToken(): string
    {
        $token = 'uptime-kita-verify-'.Str::random(32);
        $this->update(['custom_domain_verification_token' => $token]);

        return $token;
    }

    /**
     * Verify the custom domain.
     */
    public function verifyCustomDomain(): bool
    {
        if (! $this->custom_domain) {
            return false;
        }

        // Check DNS TXT record for verification
        $verified = $this->checkDnsVerification();

        if ($verified) {
            $this->update([
                'custom_domain_verified' => true,
                'custom_domain_verified_at' => now(),
            ]);
        }

        return $verified;
    }

    /**
     * Check DNS records for domain verification.
     */
    protected function checkDnsVerification(): bool
    {
        if (! $this->custom_domain || ! $this->custom_domain_verification_token) {
            return false;
        }

        try {
            $records = dns_get_record('_uptime-kita.'.$this->custom_domain, DNS_TXT);

            foreach ($records as $record) {
                if (isset($record['txt']) && $record['txt'] === $this->custom_domain_verification_token) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // DNS lookup failed
            return false;
        }

        return false;
    }

    /**
     * Get the full URL for the status page.
     */
    public function getUrl(): string
    {
        if ($this->custom_domain && $this->custom_domain_verified) {
            $protocol = $this->force_https ? 'https://' : 'http://';

            return $protocol.$this->custom_domain;
        }

        return url('/status/'.$this->path);
    }
}
