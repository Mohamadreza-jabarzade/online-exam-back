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
                    'remaining_seconds' => $remainingSeconds
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
            'otp_expires_at' => now()->addMinutes(1),
            'last_otp_sent_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'کد تایید برای شما ارسال شد',
           'data'=>[
               'remaining_seconds' => 60,
               'debug_otp' => (string) $otpCode
           ]
        ], 200);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $mobile = $request->mobile;
        $code = $request->code;

        $user = User::where('mobile', $mobile)
            ->where('otp_code', $code)
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
            'otp_expires_at' => null
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;
        $isSecure = app()->environment('production');

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد',
            'data' => [
                'mobile' => $user->mobile
            ]
        ])->cookie(
            'auth_token',
            $token,
            60 * 24 * 7,
            '/',
            null,
            $isSecure,
            true,
            false,
            'none'
        );
    }

    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => [
                'mobile' => $user->mobile
            ]
        ]);
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        $isSecure = app()->environment('production');

        return response()->json([
            'success' => true,
            'message' => 'خروج با موفقیت انجام شد'
        ])->cookie(
            'auth_token', '', -1, '/', null, $isSecure, true, false, 'lax'
        );
    }
}
