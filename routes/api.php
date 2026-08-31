<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public registration + payment endpoints (rate-limited).
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/registrations', [RegistrationController::class, 'store']);
    Route::post('/registrations/{registration}/payment', [PaymentController::class, 'store']);
    Route::get('/registrations/{number}', [RegistrationController::class, 'show']);
});
