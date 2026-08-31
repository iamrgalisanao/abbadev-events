<?php

return [
    // GCash pay-to details shown on the public payment step. Keep in sync with
    // the `paymentMethods` array on the abbadev.com /seminar page.
    'gcash' => [
        'number' => env('GCASH_NUMBER', '0928 320 7029'),
        'account_name' => env('GCASH_ACCOUNT_NAME', 'ROM***L G.'),
        'qr_url' => env('GCASH_QR_URL'),
    ],

    // Max receipt upload size in kilobytes.
    'receipt_max_kb' => (int) env('RECEIPT_MAX_KB', 5120),
];
