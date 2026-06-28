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
            'کدام پروتکل در لایه انتقال شبکه قرار دارد؟',
            'خروجی قطعه کد ارسالی در زبان PHP چیست؟',
            'مفهوم کپسوله‌سازی (Encapsulation) در کدام گزینه به درستی آمده است؟',
            'کدام یک از توابع زیر برای مدیریت نشست‌ها در مادل استفاده می‌شود؟',
            'پیچیدگی زمانی الگوریتم مرتب‌سازی سریع در بدترین حالت چقدر است؟',
            'هدف اصلی فیلد اتمپت در معماری پایگاه داده آزمون آنلاین چیست؟'
        ];

        return [
            'question_bank_id' => QuestionBank::factory(),
            'creator_id' => User::factory()->examCreator(),
            'content' => fake()->randomElement($questions),
            'type' => 'MULTIPLE_CHOICE_FOUR_OPTIONS',
        ];
    }
}
