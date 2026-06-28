<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionBankFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->examCreator(),
            'title' => fake()->randomElement([
                'بانک سوالات هوش مصنوعی',
                'مجموعه تست‌های پایگاه داده',
                'بانک سوال ریاضی ۱',
                'سوالات تستی معماری کامپیوتر'
            ]),
            'description' => fake()->sentence(),
            'is_public' => false,
        ];
    }
}
