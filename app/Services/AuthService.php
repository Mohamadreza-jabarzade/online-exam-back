<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Send OTP to mobile (Debug mode - just returns the code)
     */
    public function sendOtp(string $mobile): array
    {
        // Find or create user
        $user = User::firstOrCreate(
            ['mobile' => $mobile],
            ['name' => null] // Name will be set later
        );

        // Generate OTP
        $otpCode = $user->generateOtp();

        // TODO: Send SMS via panel (فعلاً دیباگ)
        // For debug: just log or return the code
        \Log::info("OTP for {$mobile}: {$otpCode}");

        // In development, return the code (in production, remove this)
        return [
            'message' => 'کد تایید برای شما ارسال شد',
            'debug_otp' => $otpCode, // Only for development
            'mobile' => $mobile
        ];
    }

    /**
     * Verify OTP and login/register
     */
    public function verifyOtp(string $mobile, string $code): array
    {
        $user = User::where('mobile', $mobile)->first();

        if (!$user || !$user->isOtpValid($code)) {
            throw ValidationException::withMessages([
                'code' => ['کد تایید نامعتبر یا منقضی شده است']
            ]);
        }

        // If user is new (no name set), they need to complete registration
        if (is_null($user->name)) {
            $user->clearOtp();
            return [
                'status' => 'needs_registration',
                'message' => 'لطفاً اطلاعات ثبت نام را کامل کنید',
                'mobile' => $mobile,
                'temp_token' => $user->createToken('temp-token', ['registration:complete'])->plainTextToken
            ];
        }

        // User is fully registered, login
        $user->clearOtp();
        $user->mobile_verified_at = true;
        $user->save();

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'status' => 'success',
            'message' => 'ورود موفقیت آمیز',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'mobile' => $user->mobile,
            ],
            'token' => $token
        ];
    }

    /**
     * Complete registration for new user
     */
    public function completeRegistration(string $mobile, array $data): array
    {
        $user = User::where('mobile', $mobile)->firstOrFail();

        // Validate that user hasn't completed registration yet
        if (!is_null($user->name)) {
            throw ValidationException::withMessages([
                'mobile' => ['این کاربر قبلاً ثبت نام کامل کرده است']
            ]);
        }

        // Update user data
        $user->update([
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'password' => Hash::make($data['password']),
            'mobile_verified_at' => true,
        ]);

        // Clear any existing tokens and create new one
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'message' => 'ثبت نام با موفقیت انجام شد',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'mobile' => $user->mobile,
            ],
            'token' => $token
        ];
    }

    /**
     * Logout user
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
