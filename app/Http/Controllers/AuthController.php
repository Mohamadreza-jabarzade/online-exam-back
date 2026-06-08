<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;

class AuthController extends Controller
{
    public function sendOtp(SendOtpRequest $request)
    {
        $mobile = $request->mobile;

        $user = User::where('mobile', $mobile)->first();

        // بررسی اینکه آیا کاربر وجود دارد و قبلا پیامکی گرفته یا خیر
        if ($user && $user->last_otp_sent_at) {

            // ۱. تبدیل تاریخ به آبجکت کاربن
            $lastSentAt = \Carbon\Carbon::parse($user->last_otp_sent_at);

            // ۲. محاسبه دقیق زمانی که قفل باز می‌شود (۶۰ ثانیه بعد)
            $unlockTime = $lastSentAt->copy()->addSeconds(60);

            // ۳. اگر زمان فعلی، هنوز به زمان باز شدن قفل نرسیده است:
            if (now()->isBefore($unlockTime)) {

                // با استفاده از (int) ceil اعشار کاربن ۳ رو کاملا از بین می‌بریم و رند می‌کنیم
                $remainingSeconds = (int) ceil(now()->diffInSeconds($unlockTime, false));

                return response()->json([
                    'success' => false,
                    'message' => 'لطفاً ' . $remainingSeconds . ' ثانیه دیگر دوباره تلاش کنید',
                    'remaining_seconds' => $remainingSeconds,
                ], 429);
            }
        }

        $otpCode = rand(1000, 9999);

        // اگر کاربر نبود، بسازیمش
        if (!$user) {
            $user = User::create(['mobile' => $mobile]);
        }

        // ثبت اطلاعات جدید
        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => now()->addMinute(),
            'last_otp_sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'کد تایید برای شما ارسال شد',
            'data' => [
                'remaining_seconds' => 60,
                'debug_otp' => (string) $otpCode,
                'mobile' => $mobile,
            ]
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $user = User::where('mobile', $request->mobile)
            ->where('otp_code', $request->code)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید نامعتبر است یا منقضی شده'
            ], 422);
        }

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // حذف توکن‌های قبلی (اختیاری)
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'mobile' => $user->mobile,
                'token' => $token,
            ]
        ]);
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'mobile' => auth()->user()->mobile,
            ]
        ]);
    }

    public function logout()
    {
        $user = auth()->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد'
        ]);
    }
}
