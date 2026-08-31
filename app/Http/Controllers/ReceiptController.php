<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    /**
     * Stream a payment receipt to authenticated admins only. Receipts live on
     * the private 'local' disk and are never publicly accessible.
     */
    public function show(Payment $payment): Response
    {
        abort_unless(
            $payment->receipt_path && Storage::disk('local')->exists($payment->receipt_path),
            404,
        );

        return response()->file(Storage::disk('local')->path($payment->receipt_path));
    }
}
