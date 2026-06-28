<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | ۱. ایجاد کاربران کلیدی و دانش‌آموزان همکار
            |--------------------------------------------------------------------------
            */
            // کاربر اول: طراح و مالک آزمون‌ها
            $teacher = User::create([
                'mobile' => '09011111111',
                'role' => 'user',
                'can_create_exam' => true,
            ]);

            // کاربر دوم: دانش‌آموز اصلی جهت تست روت‌ها
            $studentPrimary = User::create([
                'mobile' => '09022222222',
                'role' => 'user',
                'can_create_exam' => false,
            ]);

            // ادمین کل سیستم
            User::create([
                'mobile' => '09111111111',
                'role' => 'admin',
                'can_create_exam' => true,
            ]);

            // ایجاد ۳ دانش‌آموز دیگر برای پر کردن و شبیه‌سازی آزمون‌های شلوغ
            $otherStudents = User::factory()->count(3)->create([
                'can_create_exam' => false
            ]);

            // لیست کلیه دانش‌آموزانی که در آزمون‌ها شرکت خواهند کرد
            $allStudents = collect([$studentPrimary])->merge($otherStudents);

            /*
            |--------------------------------------------------------------------------
            | ۲. ساخت بانک سوالات مرجع طراح
            |--------------------------------------------------------------------------
            */
            $bank = QuestionBank::factory()->create([
                'user_id' => $teacher->id,
                'title' => 'بانک سوالات مرجع استاد',
            ]);

            // تولید ۳۰ سوال تستی جامع در بانک سوالات
            $questions = Question::factory()->count(30)->create([
                'question_bank_id' => $bank->id,
                'creator_id' => $teacher->id,
            ]);

            // تعریف گزینه‌های تستی چهار گزینه‌ای برای تک تک سوالات
            $questionOptionsMap = [];
            foreach ($questions as $question) {
                $correctIndex = rand(1, 4);
                for ($i = 1; $i <= 4; $i++) {
                    $option = QuestionOption::create([
                        'question_id' => $question->id,
                        'content' => "پاسخ پیشنهادی گزینه {$i} مربوط به این سوال",
                        'is_correct' => $i === $correctIndex,
                        'sort_order' => $i,
                    ]);
                    $questionOptionsMap[$question->id][] = $option;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | ۳. شبیه‌سازی سناریوی اول: آزمون‌های پیش‌نویس (Draft)
            |--------------------------------------------------------------------------
            | طبق منطق سیستم، این آزمون‌ها هیچ شرکت‌کننده‌ای ندارند.
            */
            $draftExams = Exam::factory()->count(2)->create([
                'creator_id' => $teacher->id,
                'status' => 'draft',
                'start_time' => now()->addDays(5),
                'end_time' => now()->addDays(5)->addHours(1),
            ]);
            $this->attachQuestionsToExams($draftExams, $questions);


            /*
            |--------------------------------------------------------------------------
            | ۴. شبیه‌سازی سناریوی دوم: آزمون‌های منتشر شده (Published)
            |--------------------------------------------------------------------------
            | آزمون‌هایی که در آینده برگزار می‌شوند و هنوز کسی نمی‌تواند وارد آن‌ها شود.
            */
            $publishedExams = Exam::factory()->count(2)->create([
                'creator_id' => $teacher->id,
                'status' => 'published',
                'start_time' => now()->addHours(10),
                'end_time' => now()->addHours(11),
                'published_at' => now(),
            ]);
            $this->attachQuestionsToExams($publishedExams, $questions);


            /*
            |--------------------------------------------------------------------------
            | ۵. شبیه‌سازی سناریوی سوم: آزمون‌های در حال برگزاری (In Progress)
            |--------------------------------------------------------------------------
            | آزمون فعال است. دانش‌آموز اصلی و بقیه کاربران در حال حاضر داخل جلسه هستند.
            */
            $inProgressExams = Exam::factory()->count(2)->create([
                'creator_id' => $teacher->id,
                'status' => 'in_progress',
                'start_time' => now()->subMinutes(15),
                'end_time' => now()->addMinutes(30),
                'published_at' => now()->subHours(1),
            ]);
            $this->attachQuestionsToExams($inProgressExams, $questions);

            foreach ($inProgressExams as $exam) {
                $examQuestions = $exam->questions;

                foreach ($allStudents as $student) {
                    $attempt = ExamAttempt::create([
                        'exam_id' => $exam->id,
                        'user_id' => $student->id,
                        'started_at' => now()->subMinutes(10),
                        'status' => 'in_progress', // همه در حال پاسخگویی هستند
                    ]);

                    // شبیه‌سازی این که کاربران به تعدادی از سوالات پاسخ داده‌اند
                    foreach ($examQuestions->take(4) as $q) {
                        $opts = $questionOptionsMap[$q->id];
                        ExamAnswer::create([
                            'attempt_id' => $attempt->id,
                            'question_id' => $q->id,
                            'question_option_id' => $opts[array_rand($opts)]->id,
                            'is_flagged' => fake()->boolean(15),
                        ]);
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | ۶. شبیه‌سازی سناریوی چهارم: آزمون‌های خاتمه‌یافته در انتظار تصحیح (Closed -> Submitted)
            |--------------------------------------------------------------------------
            | زمان آزمون به پایان رسیده و برگه‌ها با وضعیت submitted ذخیره شده‌اند.
            */
            $submittedExams = Exam::factory()->count(2)->create([
                'creator_id' => $teacher->id,
                'status' => 'closed',
                'start_time' => now()->subHours(5),
                'end_time' => now()->subHours(4),
                'published_at' => now()->subHours(6),
            ]);
            $this->attachQuestionsToExams($submittedExams, $questions);

            foreach ($submittedExams as $exam) {
                $examQuestions = $exam->questions;

                foreach ($allStudents as $student) {
                    $attempt = ExamAttempt::create([
                        'exam_id' => $exam->id,
                        'user_id' => $student->id,
                        'started_at' => now()->subHours(5),
                        'finished_at' => now()->subHours(4)->subMinutes(5),
                        'status' => 'submitted', // ارسال شده و منتظر تصحیح طراح
                    ]);

                    // پاسخ کامل به تمامی سوالات آزمون
                    foreach ($examQuestions as $q) {
                        $opts = $questionOptionsMap[$q->id];
                        ExamAnswer::create([
                            'attempt_id' => $attempt->id,
                            'question_id' => $q->id,
                            'question_option_id' => $opts[array_rand($opts)]->id,
                            'is_flagged' => false,
                        ]);
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | ۷. شبیه‌سازی سناریوی پنجم: آزمون‌های خاتمه‌یافته و تصحیح‌شده (Closed -> Graded/Missed)
            |--------------------------------------------------------------------------
            | برگه‌ها تصحیح شده‌اند (`graded`) و برخی کاربران هم غایب بوده‌اند (`missed`).
            */
            $gradedExams = Exam::factory()->count(2)->create([
                'creator_id' => $teacher->id,
                'status' => 'closed',
                'start_time' => now()->subDays(2),
                'end_time' => now()->subDays(2)->addHour(),
                'published_at' => now()->subDays(3),
            ]);
            $this->attachQuestionsToExams($gradedExams, $questions);

            foreach ($gradedExams as $exam) {
                $examQuestions = $exam->questions;

                foreach ($allStudents as $index => $student) {

                    // شبیه‌سازی غیبت برای یکی از دانش‌آموزان فرعی در آزمون آخر
                    if ($index === 2) {
                        ExamAttempt::create([
                            'exam_id' => $exam->id,
                            'user_id' => $student->id,
                            'status' => 'missed',
                            'started_at' => null,
                            'finished_at' => null,
                        ]);
                        continue;
                    }

                    $attempt = ExamAttempt::create([
                        'exam_id' => $exam->id,
                        'user_id' => $student->id,
                        'started_at' => now()->subDays(2),
                        'finished_at' => now()->subDays(2)->addMinutes(40),
                        'status' => 'graded', // تصحیح نهایی شده
                    ]);

                    // ثبت پاسخ‌ها
                    foreach ($examQuestions as $q) {
                        $opts = $questionOptionsMap[$q->id];
                        ExamAnswer::create([
                            'attempt_id' => $attempt->id,
                            'question_id' => $q->id,
                            'question_option_id' => $opts[array_rand($opts)]->id,
                            'is_flagged' => false,
                        ]);
                    }
                }
            }

        });
    }

    /**
     * متد کمکی برای اتصال سوالات تصادفی به آزمون‌ها در جدول پیوت
     */
    private function attachQuestionsToExams($exams, $questions): void
    {
        foreach ($exams as $exam) {
            $selectedQuestions = $questions->shuffle()->take(10)->values();
            foreach ($selectedQuestions as $index => $question) {
                DB::table('exam_questions')->insert([
                    'exam_id' => $exam->id,
                    'question_id' => $question->id,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
