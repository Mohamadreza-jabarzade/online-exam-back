<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionBankFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'title' => fake()->randomElement([
                'بانک سوال ریاضی',
                'بانک سوال فیزیک',
                'بانک سوال شیمی',
                'بانک سوال زبان',
                'بانک سوال برنامه نویسی',
            ]),

            'description' => fake()->sentence(),
        ];
    }
}
