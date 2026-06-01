<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// روت‌های عمومی (بدون احراز هویت)
Route::prefix('auth')->group(function () {
    Route::post('/otp', [AuthController::class, 'sendOtp'])->name('auth.send-otp');
    Route::post('/verify', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp');
});

// روت‌های خصوصی (نیاز به احراز هویت)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('user.profile');

    // خروج از سیستم
    Route::post('/logout', function () {
        auth()->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد'
        ]);
    })->name('auth.logout');
});
