<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    /**
     * Step 1 — create a pending registration and return the pay-to details.
     */
    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        $event = Event::where('slug', $request->validated('event'))
            ->where('is_active', true)
            ->firstOrFail();

        $registration = $this->createWithUniqueNumber($event, [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'organization' => $request->validated('organization'),
            'audience' => $request->validated('audience'),
            'source' => $request->validated('source'),
            'lead_source' => $request->validated('lead_source'),
            'meta' => ['utm' => $request->validated('utm')],
        ]);

        return response()->json([
            'registration_number' => $registration->registration_number,
            'registration_id' => $registration->id,
            'event' => [
                'slug' => $event->slug,
                'title' => $event->title,
                'expected_amount' => (float) $event->expected_amount,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'location' => $event->location,
            ],
            'payment' => [
                'method' => 'gcash',
                'amount' => (float) $event->expected_amount,
                'gcash_number' => config('payments.gcash.number'),
                'account_name' => config('payments.gcash.account_name'),
                'qr_url' => config('payments.gcash.qr_url'),
            ],
        ], 201);
    }

    /**
     * Public status lookup by registration number, for the pending screen.
     */
    public function show(string $number): JsonResponse
    {
        $registration = Registration::with('payment')
            ->where('registration_number', $number)
            ->firstOrFail();

        return response()->json([
            'registration_number' => $registration->registration_number,
            'status' => $registration->status->value,
            'payment_status' => $registration->payment?->status->value,
            'email_status' => $registration->payment?->email_status->value,
        ]);
    }

    /**
     * Create the registration, retrying once on the rare unique-number clash.
     */
    protected function createWithUniqueNumber(Event $event, array $attributes): Registration
    {
        foreach (range(1, 3) as $attempt) {
            try {
                return Registration::create(array_merge($attributes, [
                    'event_id' => $event->id,
                    'registration_number' => Registration::generateNumber($event),
                ]));
            } catch (QueryException $e) {
                if ($attempt === 3 || ! str_contains(strtolower($e->getMessage()), 'unique')) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Unable to allocate a registration number.');
    }
}
