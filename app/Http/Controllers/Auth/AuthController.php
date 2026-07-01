<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function sendOtp(SendOtpRequest $request)
    {
        $mobile = $request->mobile;

        $user = User::where('mobile', $mobile)->first();

        if ($user && $user->last_otp_sent_at) {
            $lastSentAt = \Carbon\Carbon::parse($user->last_otp_sent_at);
            $unlockTime = $lastSentAt->copy()->addSeconds(60);

            if (now()->isBefore($unlockTime)) {
                $remainingSeconds = (int) ceil(now()->diffInSeconds($unlockTime, false));

                return response()->json([
                    'success' => false,
                    'message' => 'لطفاً ' . $remainingSeconds . ' ثانیه دیگر دوباره تلاش کنید',
                    'remaining_seconds' => $remainingSeconds,
                ], 429);
            }
        }

        $otpCode = rand(1000, 9999);

        if (!$user) {
            $user = User::create(['mobile' => $mobile]);
        }

        $user->update([
            'otp_code' => $otpCode,
            'otp_expires_at' => now()->addMinute(),
            'last_otp_sent_at' => now(),
        ]);

        // TODO: اینجا باید SMS ارسال شود
        // SmsService::send($mobile, "کد تایید شما: $otpCode");

        $responseData = [
            'remaining_seconds' => 60,
            'mobile' => $mobile,
        ];

        // فقط در محیط local کد را نمایش بده (برای تست)
        if (config('app.env') === 'local') {
            $responseData['debug_otp'] = (string) $otpCode;
        }

        return response()->json([
            'success' => true,
            'message' => 'کد تایید برای شما ارسال شد',
            'data' => $responseData,
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
