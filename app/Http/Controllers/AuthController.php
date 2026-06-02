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

        if ($user && $user->last_otp_sent_at) {
            $timeSinceLastOtp = now()->diffInSeconds($user->last_otp_sent_at);
            $remainingSeconds = 60 - $timeSinceLastOtp;

            if ($remainingSeconds > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لطفاً ' . $remainingSeconds . ' ثانیه دیگر دوباره تلاش کنید',
                    'remaining_seconds' => $remainingSeconds,
                ], 429);
            }
        }

        $otpCode = rand(1000, 9999);

        $user = User::firstOrCreate(
            ['mobile' => $mobile],
            ['mobile' => $mobile]
        );

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
