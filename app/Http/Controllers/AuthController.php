<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\SignupRequest;

class AuthController extends Controller
{
    // 1. ارسال کد تایید
    public function sendOtp(SendOtpRequest $request)
    {
        $mobile = $request->mobile;

        // تولید کد 4 رقمی تصادفی
        $otpCode = rand(1000, 9999);

        // پیدا کردن کاربر یا ساختن جدید
        $user = User::firstOrCreate(
            ['mobile' => $mobile],
            [
                'mobile' => $mobile,
                'otp_code' => $otpCode,
                'otp_expires_at' => now()->addMinutes(1)
            ]
        );

        // اگه کاربر از قبل وجود داشت، فقط کد رو آپدیت کن
        if (!$user->wasRecentlyCreated) {
            $user->update([
                'otp_code' => $otpCode,
                'otp_expires_at' => now()->addMinutes(1)
            ]);
        }

        // برگردوندن پاسخ موفق (بدون سرویس پیامکی واقعی)
        return response()->json([
            'success' => true,
            'message' => 'کد تایید برای شما ارسال شد',
            'debug_otp' => (string) $otpCode
        ], 200);
    }

    // 2. تایید کد
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $mobile = $request->mobile;
        $code = $request->code;

        // پیدا کردن کاربر با موبایل و کد معتبر
        $user = User::where('mobile', $mobile)
            ->where('otp_code', $code)
            ->where('otp_expires_at', '>', now())
            ->first();

        // اگه کاربر وجود نداشت یا کد اشتباه یا منقضی شده بود
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید نامعتبر است یا منقضی شده'
            ], 422);
        }

        // پاک کردن کد تایید (یکبار مصرف)
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        // چک کردن اینکه آیا کاربر پروفایل کامل داره یا نه
        $isProfileCompleted = $user->is_profile_completed;

        // برگردوندن پاسخ موفق
        return response()->json([
            'success' => true,
            'data' => [
                'is_user_exist' => true,
                'is_profile_completed' => $isProfileCompleted,
                'token' => $user->createToken('auth-token')->plainTextToken
            ]
        ], 200);
    }

    // 3. تکمیل ثبت‌نام (نام و نام خانوادگی)
    public function signup(SignupRequest $request)
    {
        // پیدا کردن کاربر با توکن (که توی هدر Authorization میاد)
        $user = $request->user();

        // اگه کاربر احراز هویت نشده بود
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'لطفا ابتدا کد تایید را وارد کنید'
            ], 401);
        }

        // آپدیت نام و نام خانوادگی
        $user->update([
            'name' => $request->name,
            'family' => $request->family,
            'is_profile_completed' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ثبت‌نام با موفقیت انجام شد'
        ], 200);
    }

    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $user->name,
                'family' => $user->family,
                'mobile' => $user->mobile
            ]
        ], 200);
    }
}
