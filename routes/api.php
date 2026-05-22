<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Public routes
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // For users who need to complete registration
    Route::post('/auth/complete-registration', [AuthController::class, 'completeRegistration'])
        ->middleware('abilities:registration:complete');

    // For authenticated users
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});
