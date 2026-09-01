<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'type',
        'mode',
        'event_code',
        'expected_amount',
        'price_label',
        'starts_at',
        'duration',
        'level',
        'location',
        'audience',
        'blurb',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'audience' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function isFree(): bool
    {
        return (float) $this->expected_amount <= 0;
    }

    /**
     * Public API shape for the marketing site's session cards.
     */
    public function toCard(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type,
            'mode' => $this->mode,
            'location' => $this->location,
            'audience' => $this->audience ?? [],
            'date' => $this->starts_at?->format('M j, Y'),
            'time' => $this->starts_at ? $this->starts_at->format('g:i A') . ' PHT' : null,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'duration' => $this->duration,
            'level' => $this->level,
            'blurb' => $this->blurb,
            'price_label' => $this->price_label ?? ($this->isFree() ? 'Free' : null),
            'expected_amount' => (float) $this->expected_amount,
            'is_free' => $this->isFree(),
            'is_featured' => $this->is_featured,
        ];
    }
}
