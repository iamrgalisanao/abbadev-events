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
     * The sequence is per event-code prefix and year, not per event: the unique
     * index on registration_number is global, so two events sharing a code
     * (e.g. several seminars as SEM) must share one series or they would clash.
     * It is derived from the highest number ever issued rather than a row
     * count, so deleting a registration never reissues a number. Ordering by
     * length first keeps the max numeric once the suffix grows past 4 digits
     * (a plain string max would rank 9999 above 10000). lockForUpdate
     * serialises concurrent allocations when the caller wraps this in a
     * transaction; the unique index and the caller's retry loop remain the
     * final backstop.
     */
    public static function generateNumber(Event $event): string
    {
        $year = now()->format('Y');
        $prefix = "ABBA-{$event->event_code}-{$year}-";

        $lastNumber = static::where('registration_number', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(registration_number) desc')
            ->orderBy('registration_number', 'desc')
            ->lockForUpdate()
            ->value('registration_number');

        $next = $lastNumber ? ((int) substr($lastNumber, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
