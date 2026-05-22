<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/auth/otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify', [AuthController::class, 'verifyOtp']);

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/auth/signup', [AuthController::class, 'signup']);
    Route::get('/me', [AuthController::class, 'me']); // ← روت جدید
});
