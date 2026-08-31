<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'event_code',
        'expected_amount',
        'starts_at',
        'location',
        'is_active',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}
