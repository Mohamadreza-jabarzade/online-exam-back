<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ExamManagement\ExamController as ExamManagementController;
use App\Http\Controllers\ExamManagement\ResultController;
use App\Http\Controllers\ExamProcess\ExamController as  ExamProcessController;
use App\Http\Controllers\ExamManagement\QuestionBankController;
use App\Http\Controllers\ExamManagement\QuestionController;
use App\Http\Controllers\ExamProcess\ExamAnswerController;
use Illuminate\Support\Facades\Route;

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
        Route::apiResource('exams', ExamManagementController::class);
        Route::apiResource('banks', QuestionBankController::class);
        Route::get('/bank/{bank}/questions', [QuestionBankController::class, 'bankQuestions']);
        Route::post('/bank/{bank}/question', [QuestionBankController::class, 'bankQuestionStore']);
        Route::apiResource('questions', QuestionController::class);
    });

    Route::prefix('exams')->group(function () {
        // دریافت وضعیت کلی آزمون بر اساس لینک تصادفی
        Route::get('{exam:uuid}', [ExamProcessController::class, 'show']);
        // شروع رسمی آزمون
        Route::get('{exam:uuid}/start', [ExamProcessController::class, 'start']);
        // دریافت سوالات و وضعیت جلسه آزمون در حال اجرا
        Route::get('{exam:uuid}/attempt', [ExamProcessController::class, 'attempt']);
        // ذخیره یا ویرایش پاسخ هر سوال به صورت آنلاین
        Route::post('{exam:uuid}/save-answer', [ExamProcessController::class, 'saveAnswer']);
        // خاتمه دادن و ثبت نهایی آزمون
        Route::get('{exam:uuid}/finish', [ExamProcessController::class, 'finish']);
    });

    Route::prefix('results')->group(function () {
        Route::get('/stats', [ResultController::class, 'stats']);
        Route::get('exam/{exam}', [ResultController::class, 'examResult']);
        Route::get('attempt/{attempt}', [ResultController::class, 'examResult']); //result/attempt/12 -> data : { questions:[{},{},{}],attempt: {mobile:"", } }
    });

    // اطلاعات کاربر لاگین شده
    Route::get('/me', [AuthController::class, 'me'])
        ->name('user.profile');

    // خروج از حساب
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');
});
