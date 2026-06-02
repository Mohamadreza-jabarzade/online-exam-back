<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    // ارسال کد تایید
    Route::post('/otp', [AuthController::class, 'sendOtp'])
        ->name('auth.send-otp');

    // تایید کد و دریافت توکن
    Route::post('/verify', [AuthController::class, 'verifyOtp'])
        ->name('auth.verify-otp');
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // اطلاعات کاربر لاگین شده
    Route::get('/me', [AuthController::class, 'me'])
        ->name('user.profile');

    // خروج از حساب
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');
});
