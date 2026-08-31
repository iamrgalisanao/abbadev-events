<?php

namespace App\Services;

use App\Enums\EmailStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private N8nNotifier $notifier)
    {
    }

    /**
     * Confirm a verified payment: mark the payment verified, confirm the
     * registration, and record who/when — all in one transaction. Then notify
     * n8n (email + Telegram) and record the email status.
     */
    public function confirm(Payment $payment, User $verifier, ?string $notes = null): Payment
    {
        DB::transaction(function () use ($payment, $verifier, $notes) {
            $payment->forceFill([
                'status' => PaymentStatus::Verified,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'verification_notes' => $notes,
            ])->save();

            $payment->registration->forceFill([
                'status' => RegistrationStatus::Confirmed,
            ])->save();
        });

        $this->sendConfirmationEmail($payment);

        return $payment->refresh();
    }

    /**
     * Mark a payment rejected / duplicate / amount_mismatch without confirming
     * the registration or sending an email.
     */
    public function setStatus(Payment $payment, PaymentStatus $status, User $verifier, ?string $notes = null): Payment
    {
        $payment->forceFill([
            'status' => $status,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ])->save();

        return $payment->refresh();
    }

    protected function sendConfirmationEmail(Payment $payment): void
    {
        $registration = $payment->registration;
        $event = $registration->event;

        $ok = $this->notifier->paymentConfirmed([
            'event' => 'registration.payment_confirmed',
            'registration_id' => $registration->id,
            'registration_number' => $registration->registration_number,
            'name' => $registration->name,
            'email' => $registration->email,
            'phone' => $registration->phone,
            'event_name' => $event?->title,
            'event_date' => $event?->starts_at?->format('M j, Y · g:i A'),
            'location' => $event?->location,
            'amount' => (float) $payment->expected_amount,
            'payment_method' => $payment->payment_method,
            'payment_reference' => $payment->reference_number,
        ]);

        $payment->forceFill([
            'email_status' => $ok ? EmailStatus::Sent : EmailStatus::Failed,
            'confirmation_email_sent_at' => $ok ? now() : null,
        ])->save();
    }
}
