<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Registration;
use App\Services\N8nNotifier;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Step 2 — attach a GCash payment (receipt + reference) to a registration
     * and queue it for admin verification, flagging duplicates / mismatches.
     */
    public function store(StorePaymentRequest $request, Registration $registration, N8nNotifier $notifier): JsonResponse
    {
        $registration->loadMissing('event');
        $expected = (float) $registration->event->expected_amount;
        $amount = (float) $request->validated('amount_submitted');
        $reference = $request->validated('reference_number');

        $isDuplicate = Payment::where('reference_number', $reference)->exists();
        $isMismatch = $amount !== $expected;

        $status = match (true) {
            $isDuplicate => PaymentStatus::Duplicate,
            $isMismatch => PaymentStatus::AmountMismatch,
            default => PaymentStatus::ForVerification,
        };

        $receiptPath = $request->file('receipt')->store('receipts', 'local');

        $payment = $registration->payments()->create([
            'payment_method' => 'gcash',
            'expected_amount' => $expected,
            'amount_submitted' => $amount,
            'reference_number' => $reference,
            'receipt_path' => $receiptPath,
            'status' => $status,
        ]);

        // Best-effort "payment received, verifying" email (optional webhook).
        $notifier->registrationReceived([
            'event' => 'registration.payment_received',
            'registration_number' => $registration->registration_number,
            'name' => $registration->name,
            'email' => $registration->email,
            'event_name' => $registration->event->title,
            'amount' => $amount,
            'payment_reference' => $reference,
        ]);

        return response()->json([
            'registration_number' => $registration->registration_number,
            'payment_status' => $payment->status->value,
            'flags' => [
                'duplicate' => $isDuplicate,
                'amount_mismatch' => $isMismatch,
            ],
            'message' => "Payment received. We'll verify it and email you once your seat is confirmed.",
        ], 201);
    }
}
