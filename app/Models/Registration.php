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
     * Sequence is per-event, per-year, derived from the highest number ever
     * issued (MAX) rather than a row count — so deleting a registration never
     * reissues an existing number. The 4-digit zero-padded suffix means the
     * lexical max equals the numeric max. lockForUpdate serialises concurrent
     * allocations when the caller wraps this in a transaction; the unique index
     * and the caller's retry loop remain the final backstop.
     */
    public static function generateNumber(Event $event): string
    {
        $year = now()->format('Y');
        $prefix = "ABBA-{$event->event_code}-{$year}-";

        $lastNumber = static::where('event_id', $event->id)
            ->where('registration_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max('registration_number');

        $next = $lastNumber ? ((int) substr($lastNumber, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
