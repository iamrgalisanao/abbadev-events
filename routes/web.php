<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RegistrationExportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Admin-only CSV export of all registrations. Path is deliberately outside
// /admin/registrations/{record} so it doesn't collide with the Filament resource.
Route::get('/admin/registrations-export', [RegistrationExportController::class, 'index'])
    ->middleware('auth')
    ->name('admin.registrations.export');

// Stream a payment receipt to authenticated admins only. Uses a controller (not
// a closure) so `route:cache` works in production.
Route::get('/admin/receipts/{payment}', [ReceiptController::class, 'show'])
    ->middleware('auth')
    ->name('admin.receipts');

// Public certificate verification — the destination of the QR code printed on
// every issued certificate. No auth: anyone holding the credential ID can
// check it, which is the point.
Route::prefix('verify')
    ->name('certificates.')
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::get('/{credential}', [CertificateVerificationController::class, 'show'])->name('verify');
        Route::get('/{credential}/checks', [CertificateVerificationController::class, 'checks'])->name('checks');
        Route::get('/{credential}/download', [CertificateVerificationController::class, 'download'])->name('download');
    });

// Signature images are shared across certificates, so they hang off their own
// path rather than a credential's.
Route::get('/certificate-signature/{file}', [CertificateVerificationController::class, 'signature'])
    ->where('file', '[A-Za-z0-9._-]+')
    ->middleware('throttle:120,1')
    ->name('certificates.signature');
