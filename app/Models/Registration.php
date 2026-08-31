<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    protected $fillable = [
        'registration_number',
        'name',
        'email',
        'phone',
        'organization',
        'event_id',
        'status',
        'audience',
        'source',
        'lead_source',
        'meta',
    ];

    protected $casts = [
        'status' => RegistrationStatus::class,
        'meta' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Generate the next registration number for an event, e.g. ABBA-SEM-2026-0041.
     *
     * Sequence is per-event, per-year. Low-volume Phase 1 so a count-based
     * sequence is acceptable; the unique index on registration_number is the
     * backstop against a rare concurrent collision (caller retries on clash).
     */
    public static function generateNumber(Event $event): string
    {
        $year = now()->format('Y');
        $prefix = "ABBA-{$event->event_code}-{$year}-";

        $count = static::where('event_id', $event->id)
            ->whereYear('created_at', $year)
            ->count();

        return $prefix . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
