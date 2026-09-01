<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public endpoints (rate-limited).
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/registrations', [RegistrationController::class, 'store']);
    Route::post('/registrations/{registration}/payment', [PaymentController::class, 'store']);
    Route::get('/registrations/{number}', [RegistrationController::class, 'show']);
});
