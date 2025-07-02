<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusPage extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'icon',
        'path',
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
        $basePath = \Str::slug($title);
        $path = $basePath;
        $counter = 1;

        while (static::where('path', $path)->exists()) {
            $path = $basePath . '-' . $counter;
            $counter++;
        }

        return $path;
    }
}
