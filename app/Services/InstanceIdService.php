<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstanceIdService
{
    /**
     * Get or create the anonymous instance ID.
     *
     * The ID is a one-way hash that cannot be reversed to identify
     * the installation or its owner.
     */
    public function getInstanceId(): string
    {
        $path = config('telemetry.instance_id_path');

        if (File::exists($path)) {
            return trim(File::get($path));
        }

        $instanceId = $this->generateInstanceId();

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $instanceId);

        return $instanceId;
    }

    /**
     * Generate a new anonymous instance ID.
     *
     * Uses a combination of random data hashed with SHA-256
     * to create a unique, non-reversible identifier.
     */
    protected function generateInstanceId(): string
    {
        // Combine random bytes with current timestamp for uniqueness
        $randomData = Str::random(64).microtime(true).random_bytes(32);

        // Create SHA-256 hash (64 characters, not reversible)
        return hash('sha256', $randomData);
    }

    /**
     * Get the installation date (first time instance ID was created).
     */
    public function getInstallDate(): ?string
    {
        $path = config('telemetry.instance_id_path');

        if (File::exists($path)) {
            $timestamp = File::lastModified($path);

            return date('Y-m-d', $timestamp);
        }

        return date('Y-m-d'); // Today if no ID exists yet
    }

    /**
     * Regenerate the instance ID (for privacy-conscious users).
     */
    public function regenerateInstanceId(): string
    {
        $path = config('telemetry.instance_id_path');

        if (File::exists($path)) {
            File::delete($path);
        }

        return $this->getInstanceId();
    }

    /**
     * Check if instance ID exists.
     */
    public function hasInstanceId(): bool
    {
        return File::exists(config('telemetry.instance_id_path'));
    }
}
