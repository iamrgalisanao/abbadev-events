<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nNotifier
{
    /**
     * POST a payload to a configured n8n webhook with Header Auth.
     * Returns true on a 2xx response, false otherwise (never throws).
     */
    public function send(?string $url, ?string $token, array $payload): bool
    {
        if (! $url) {
            Log::warning('n8n webhook URL not configured; skipping notification', [
                'payload_event' => $payload['event'] ?? null,
            ]);

            return false;
        }

        try {
            $request = Http::timeout((int) config('n8n.timeout', 10))
                ->acceptJson()
                ->asJson();

            if ($token) {
                $request = $request->withToken($token);
            }

            $response = $request->post($url, $payload);

            if (! $response->successful()) {
                Log::error('n8n webhook returned a non-2xx response', [
                    'status' => $response->status(),
                    'event' => $payload['event'] ?? null,
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('n8n webhook call failed', [
                'message' => $e->getMessage(),
                'event' => $payload['event'] ?? null,
            ]);

            return false;
        }
    }

    public function paymentConfirmed(array $payload): bool
    {
        return $this->send(
            config('n8n.payment_confirmed_url'),
            config('n8n.payment_confirmed_token'),
            $payload,
        );
    }

    public function registrationReceived(array $payload): bool
    {
        return $this->send(
            config('n8n.registration_received_url'),
            config('n8n.registration_received_token'),
            $payload,
        );
    }
}
