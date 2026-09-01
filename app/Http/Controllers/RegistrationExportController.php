<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationExportController extends Controller
{
    /**
     * Stream all registrations (with their latest payment) as a CSV download.
     * Synchronous and chunked — no queue worker needed at this scale.
     */
    public function index(): StreamedResponse
    {
        $filename = 'abbadev-registrations-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Registration #', 'Name', 'Email', 'Phone', 'Organization', 'Audience',
                'Event', 'Registration status', 'Lead source', 'UTM campaign',
                'Payment status', 'Expected', 'Submitted', 'GCash reference',
                'Email status', 'Verified at', 'Registered at',
            ]);

            Registration::with(['event', 'payment'])
                ->orderBy('id')
                ->chunk(200, function ($registrations) use ($out): void {
                    foreach ($registrations as $registration) {
                        $payment = $registration->payment;

                        fputcsv($out, [
                            $registration->registration_number,
                            $registration->name,
                            $registration->email,
                            $registration->phone,
                            $registration->organization,
                            $registration->audience,
                            $registration->event?->title,
                            $registration->status->value,
                            $registration->lead_source,
                            data_get($registration->meta, 'utm.utm_campaign'),
                            $payment?->status->value,
                            $payment?->expected_amount,
                            $payment?->amount_submitted,
                            $payment?->reference_number,
                            $payment?->email_status->value,
                            $payment?->verified_at?->format('Y-m-d H:i'),
                            $registration->created_at->format('Y-m-d H:i'),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
