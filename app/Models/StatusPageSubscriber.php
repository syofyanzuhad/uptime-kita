<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StatusPageSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_page_id',
        'email',
        'verification_token',
        'verified_at',
        'unsubscribe_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Boot function to generate tokens automatically on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (StatusPageSubscriber $subscriber) {
            if (! $subscriber->unsubscribe_token) {
                $subscriber->unsubscribe_token = Str::random(32);
            }
            if (! $subscriber->verified_at && ! $subscriber->verification_token) {
                $subscriber->verification_token = Str::random(32);
            }
        });
    }

    /**
     * Get the status page that this subscriber belongs to.
     */
    public function statusPage(): BelongsTo
    {
        return $this->belongsTo(StatusPage::class);
    }

    /**
     * Scope a query to only include verified subscribers.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Mark the subscriber as verified.
     */
    public function markAsVerified(): bool
    {
        return $this->update([
            'verified_at' => now(),
            'verification_token' => null,
        ]);
    }
}
