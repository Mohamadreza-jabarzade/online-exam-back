<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mobile' => '09' . fake()->unique()->numerify('#########'),
            'role' => 'user',
            'can_create_exam' => false, // پیش‌فرض دانش‌آموز است
            'otp_code' => null,
            'otp_expires_at' => null,
            'last_otp_sent_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'can_create_exam' => true,
        ]);
    }

    public function examCreator(): static
    {
        return $this->state(fn () => [
            'role' => 'user',
            'can_create_exam' => true,
        ]);
    }
}
