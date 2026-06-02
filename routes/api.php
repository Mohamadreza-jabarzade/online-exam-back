<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// روت‌های عمومی
Route::prefix('auth')->group(function () {
    Route::post('/otp', [AuthController::class, 'sendOtp'])->name('auth.send-otp');
    Route::post('/verify', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp');
});

// روت‌های خصوصی
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('user.profile');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});
