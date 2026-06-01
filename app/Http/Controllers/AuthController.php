<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use Carbon\Carbon;

class AuthController extends Controller
{
    // 1. ارسال کد تایید (با محدودیت زمانی)
    public function sendOtp(SendOtpRequest $request)
    {
        $mobile = $request->mobile;

        // پیدا کردن کاربر
        $user = User::where('mobile', $mobile)->first();

        // بررسی آخرین زمان ارسال کد (برای تایمر)
        if ($user && $user->last_otp_sent_at) {
            $timeSinceLastOtp = now()->diffInSeconds($user->last_otp_sent_at);
            $remainingSeconds = 60 - $timeSinceLastOtp;

            // اگر کمتر از 1 دقیقه از ارسال قبلی گذشته بود
            if ($remainingSeconds > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لطفاً ' . $remainingSeconds . ' ثانیه دیگر دوباره تلاش کنید',
                    'remaining_seconds' => $remainingSeconds
                ], 429); // 429 Too Many Requests
            }
        }

        // تولید کد 4 رقمی تصادفی
        $otpCode = rand(1000, 9999);

        // پیدا کردن کاربر یا ساختن جدید
        $user = User::firstOrCreate(
            ['mobile' => $mobile],
            ['mobile' => $mobile]
        );

        // آپدیت کد و زمان ارسال
        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => now()->addMinutes(1),
            'last_otp_sent_at' => now() // برای تایمر
        ]);

        // برگردوندن پاسخ موفق به همراه زمان باقی‌مانده (60 ثانیه)
        return response()->json([
            'success' => true,
            'message' => 'کد تایید برای شما ارسال شد',
            'remaining_seconds' => 60, // زمان مجاز برای ارسال مجدد
            'debug_otp' => (string) $otpCode // فقط برای تست
        ], 200);
    }

    // 2. تایید کد و ورود/ثبت‌نام
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

        // برگردوندن توکن برای دسترسی
        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'token' => $user->createToken('auth-token')->plainTextToken,
                'mobile' => $user->mobile
            ]
        ], 200);
    }

    // 3. دریافت اطلاعات کاربر جاری
    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'mobile' => $user->mobile
            ]
        ], 200);
    }
}
