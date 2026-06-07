<?php

use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionBankController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    // ارسال کد تایید
    Route::post('/otp', [AuthController::class, 'sendOtp'])
        ->name('auth.send-otp');
    // تایید کد و دریافت توکن
    Route::post('/verify', [AuthController::class, 'verifyOtp'])
        ->name('auth.verify-otp');
});


Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('dashboard')->group(function () {
        // روت های مدیریت ازمون ها در داشبورد
        Route::apiResource('exams', ExamController::class);
        Route::apiResource('banks', QuestionBankController::class);
    });


    // اطلاعات کاربر لاگین شده
    Route::get('/me', [AuthController::class, 'me'])
        ->name('user.profile');
    // خروج از حساب
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');
});
