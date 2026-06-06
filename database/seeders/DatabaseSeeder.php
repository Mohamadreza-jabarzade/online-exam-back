<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Exam;
use App\Models\ExamLink;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Admin
            |--------------------------------------------------------------------------
            */

            $admin = User::create([
                'mobile' => '09111111111',
                'role' => 'admin',
                'can_create_exam' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Users
            |--------------------------------------------------------------------------
            */

            $users = User::factory()
                ->count(20)
                ->create();

            /*
            |--------------------------------------------------------------------------
            | Question Banks
            |--------------------------------------------------------------------------
            */

            $banks = collect();

            for ($i = 0; $i < 5; $i++) {

                $owner = $users->random();

                $bank = QuestionBank::factory()->create([
                    'user_id' => $owner->id,
                ]);

                $banks->push($bank);
            }

            /*
            |--------------------------------------------------------------------------
            | Questions + Options
            |--------------------------------------------------------------------------
            */

            foreach ($banks as $bank) {

                $questions = Question::factory()
                    ->count(20)
                    ->create([
                        'question_bank_id' => $bank->id,
                        'creator_id' => $bank->user_id,
                    ]);

                foreach ($questions as $question) {

                    $correctIndex = rand(1, 4);

                    for ($i = 1; $i <= 4; $i++) {

                        QuestionOption::create([
                            'question_id' => $question->id,

                            'content' => "گزینه {$i}",

                            'is_correct' => $i === $correctIndex,

                            'sort_order' => $i,
                        ]);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Exams
            |--------------------------------------------------------------------------
            */

            $exams = Exam::factory()
                ->count(10)
                ->published()
                ->create([
                    'creator_id' => fn () => $users->random()->id,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Exam Questions
            |--------------------------------------------------------------------------
            */

            $allQuestionIds = Question::pluck('id');

            foreach ($exams as $exam) {

                $selectedQuestions = $allQuestionIds
                    ->shuffle()
                    ->take(rand(10, 20))
                    ->values();

                foreach ($selectedQuestions as $index => $questionId) {

                    DB::table('exam_questions')->insert([
                        'exam_id' => $exam->id,

                        'question_id' => $questionId,

                        'sort_order' => $index + 1,

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

        });
    }
}
