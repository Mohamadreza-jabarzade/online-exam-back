<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creator_id' => User::factory()->examCreator(),
            'title' => fake()->randomElement([
                'آزمون میان‌ترم ریاضی عمومی',
                'مبانی برنامه‌نویسی وب',
                'آزمون فیزیک الکتریسیته',
                'ساختار داده‌ها و الگوریتم‌ها',
                'امنیت شبکه‌های کامپیوتری'
            ]),
            'description' => fake()->paragraph(),
            'duration_minutes' => fake()->randomElement([20, 30, 45, 60]),
            'show_result' => true,
            'show_correct_answers' => true,
            'random_questions' => fake()->boolean(),
            'random_options' => fake()->boolean(),
            'status' => 'draft',
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addMinutes(30),
            'published_at' => null,
        ];
    }
}
