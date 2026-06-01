<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // لیست شماره‌های موبایل معتبر برای تست
        $validMobiles = [
            '09123456789',
            '09123456788',
            '09123456787',
            '09123456786',
            '09123456785',
            '09120000001',
            '09130000002',
            '09120000003',
            '09350000004',
            '09360000005'
        ];

        return [
            'mobile' => fake()->unique()->regexify('09[0-9]{9}'), // تولید شماره 11 رقمی تصادفی
            'role' => fake()->randomElement(['user', 'admin']), // نقش تصادفی (بیشتر کاربر عادی)
            'otp_code' => null, // در حالت عادی null است
            'otp_expires_at' => null, // در حالت عادی null است
            'last_otp_sent_at' => null, // در حالت عادی null است
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user has a valid OTP code.
     *
     * @param string $code
     * @param int $expiresInMinutes
     */
    public function withOtp(string $code = '1234', int $expiresInMinutes = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes($expiresInMinutes),
            'last_otp_sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the user's OTP code is expired.
     *
     * @param string $code
     */
    public function withExpiredOtp(string $code = '1234'): static
    {
        return $this->state(fn (array $attributes) => [
            'otp_code' => $code,
            'otp_expires_at' => now()->subMinutes(1), // 1 دقیقه قبل منقضی شده
            'last_otp_sent_at' => now()->subMinutes(2),
        ]);
    }

    /**
     * Indicate that the user has recently received an OTP (for timer testing).
     *
     * @param int $secondsAgo
     */
    public function recentlySentOtp(int $secondsAgo = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'otp_code' => '5678',
            'otp_expires_at' => now()->addMinutes(1),
            'last_otp_sent_at' => now()->subSeconds($secondsAgo),
        ]);
    }

    /**
     * Create a user with a specific mobile number.
     *
     * @param string $mobile
     */
    public function withMobile(string $mobile): static
    {
        return $this->state(fn (array $attributes) => [
            'mobile' => $mobile,
        ]);
    }
}
