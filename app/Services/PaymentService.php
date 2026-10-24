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
     * Bulk-confirm payments, DB side only (fast, no network). Each payment is
     * confirmed in its own transaction so one failure never rolls back the
     * rest, and its row is re-read under a lock so a payment another admin has
     * already handled is never confirmed twice. Only payments still awaiting
     * verification qualify: amount-mismatch, duplicate, rejected and already
     * verified rows are skipped, never silently confirmed.
     *
     * Emails are not sent here; pass the returned payments to
     * sendConfirmationEmails() once the response is out.
     *
     * @param  iterable<Payment>  $payments
     * @return array{confirmed: list<Payment>, skipped: int}
     */
    public function confirmMany(iterable $payments, User $verifier, ?string $notes = null): array
    {
        $confirmed = [];
        $skipped = 0;

        foreach ($payments as $payment) {
            $done = DB::transaction(function () use ($payment, $verifier, $notes) {
                $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->first();

                if (! $locked || $locked->status !== PaymentStatus::ForVerification) {
                    return null;
                }

                $locked->forceFill([
                    'status' => PaymentStatus::Verified,
                    'verified_by' => $verifier->id,
                    'verified_at' => now(),
                    'verification_notes' => $notes,
                ])->save();

                $locked->registration->forceFill([
                    'status' => RegistrationStatus::Confirmed,
                ])->save();

                return $locked;
            });

            $done ? $confirmed[] = $done : $skipped++;
        }

        return ['confirmed' => $confirmed, 'skipped' => $skipped];
    }

    /**
     * Send (or resend) the confirmation email for verified payments, one at a
     * time. Payments whose email already went out are skipped, so running this
     * twice never double-emails a registrant.
     *
     * @param  iterable<Payment>  $payments
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function sendConfirmationEmails(iterable $payments): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($payments as $payment) {
            $payment->refresh();

            if ($payment->status !== PaymentStatus::Verified || $payment->email_status === EmailStatus::Sent) {
                $result['skipped']++;

                continue;
            }

            $this->sendConfirmationEmail($payment);
            $payment->refresh()->email_status === EmailStatus::Sent ? $result['sent']++ : $result['failed']++;
        }

        return $result;
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
