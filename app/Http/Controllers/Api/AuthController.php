<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Requests\Api\CompleteRegistrationRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Send OTP to mobile
     */
    public function sendOtp(SendOtpRequest $request)
    {
        $result = $this->authService->sendOtp($request->mobile);

        return response()->json($result, 200);
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $result = $this->authService->verifyOtp(
            $request->mobile,
            $request->code
        );

        return response()->json($result, 200);
    }

    /**
     * Complete registration for new user
     */
    public function completeRegistration(CompleteRegistrationRequest $request)
    {
        $user = $request->user();

        // Check if user needs to complete registration
        if (!is_null($user->name)) {
            return response()->json([
                'message' => 'این کاربر قبلاً ثبت نام کامل کرده است'
            ], 400);
        }

        // Get mobile from the authenticated user
        $mobile = $user->mobile;

        $result = $this->authService->completeRegistration($mobile, $request->validated());

        return response()->json($result, 200);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'خروج با موفقیت انجام شد'
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ], 200);
    }
}
