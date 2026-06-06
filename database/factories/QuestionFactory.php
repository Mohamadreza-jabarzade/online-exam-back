<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        $questions = [
            'حاصل 2 + 2 چند است؟',
            'پایتخت ایران کدام شهر است؟',
            'PHP یک زبان چیست؟',
            'Laravel بر پایه کدام زبان ساخته شده است؟',
            'CPU مخفف چیست؟',
            'حاصل 10 × 10 چند است؟',
            'HTML مخفف چیست؟',
            'کدام مورد سیستم مدیریت پایگاه داده است؟',
        ];

        return [
            'question_bank_id' => QuestionBank::factory(),

            'creator_id' => User::factory(),

            'content' => fake()->randomElement($questions),

            'type' => 'mcq_single',
        ];
    }
}
