<?php

namespace App\Models;

use App\Enums\EmailStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'registration_id',
        'payment_method',
        'expected_amount',
        'amount_submitted',
        'reference_number',
        'receipt_path',
        'status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'email_status',
        'confirmation_email_sent_at',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'amount_submitted' => 'decimal:2',
        'status' => PaymentStatus::class,
        'email_status' => EmailStatus::class,
        'verified_at' => 'datetime',
        'confirmation_email_sent_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function hasAmountMismatch(): bool
    {
        return $this->amount_submitted !== null
            && (float) $this->amount_submitted !== (float) $this->expected_amount;
    }
}
