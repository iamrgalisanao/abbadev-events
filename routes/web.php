<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Stream a payment receipt to authenticated admins only. Uses a controller (not
// a closure) so `route:cache` works in production.
Route::get('/admin/receipts/{payment}', [ReceiptController::class, 'show'])
    ->middleware('auth')
    ->name('admin.receipts');
