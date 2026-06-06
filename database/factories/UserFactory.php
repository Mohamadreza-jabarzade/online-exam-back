<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mobile' => '09' . fake()->unique()->numerify('#########'),

            'role' => fake()->randomElement([
                'user',
                'user',
                'user',
                'admin'
            ]),

            'can_create_exam' => fake()->boolean(70),

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
