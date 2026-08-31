<?php

return [
    // Fired when an admin confirms a verified payment. Sends the confirmation
    // email + Telegram alert from n8n.
    'payment_confirmed_url' => env('N8N_PAYMENT_CONFIRMED_URL'),
    'payment_confirmed_token' => env('N8N_PAYMENT_CONFIRMED_TOKEN'),

    // Optional: fired when a registrant submits their payment for verification
    // ("payment received, we're verifying it") email.
    'registration_received_url' => env('N8N_REGISTRATION_RECEIVED_URL'),
    'registration_received_token' => env('N8N_REGISTRATION_RECEIVED_TOKEN'),

    'timeout' => (int) env('N8N_TIMEOUT', 10),
];
