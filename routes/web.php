<?php

use App\Models\Payment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

Route::get('/', fn () => redirect('/admin'));

// Stream a payment receipt to authenticated admins only. Receipts live on the
// private 'local' disk and are never publicly accessible.
Route::get('/admin/receipts/{payment}', function (Payment $payment): Response {
    abort_unless(
        $payment->receipt_path && Storage::disk('local')->exists($payment->receipt_path),
        404,
    );

    return response()->file(Storage::disk('local')->path($payment->receipt_path));
})->middleware('auth')->name('admin.receipts');
