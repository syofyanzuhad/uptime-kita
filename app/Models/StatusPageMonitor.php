<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusPageMonitor extends Model
{
    use HasFactory;

    protected $table = 'status_page_monitor';

    protected $fillable = [
        'status_page_id',
        'monitor_id',
        'order',
    ];

    /**
     * Get the status page that owns the monitor.
     */
    public function statusPage(): BelongsTo
    {
        return $this->belongsTo(StatusPage::class);
    }

    /**
     * Get the monitor associated with this status page monitor.
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}
