<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),

            'title' => fake()->randomElement([
                'آزمون ریاضی',
                'آزمون فیزیک',
                'آزمون شیمی',
                'آزمون برنامه نویسی',
                'آزمون زبان انگلیسی',
            ]),

            'description' => fake()->paragraph(),

            'duration_minutes' => fake()->randomElement([
                15,
                20,
                30,
                45,
                60,
                90,
            ]),

            'show_result' => true,

            'show_correct_answers' => fake()->boolean(),

            'random_questions' => fake()->boolean(),

            'random_options' => fake()->boolean(),
            'start_time' => fake()->dateTime(),

            'status' => fake()->randomElement([
                'draft',
                'published',
                'closed',
            ]),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
        ]);
    }
}
