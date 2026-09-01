<?php

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
