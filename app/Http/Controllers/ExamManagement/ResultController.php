<?php

namespace App\Http\Controllers\ExamManagement;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function examResult(Exam $exam): JsonResponse
    {
        // ۱. بارگذاری تعداد کل سوالات آزمون برای نمایش در خروجی
        $exam->loadCount('questions');

        // ۲. دریافت لیست تمام تلاش‌ها به همراه اطلاعات کاربر و شمارش پاسخ‌های درست
        $takers = $exam->attempts()
            ->with('user')
            ->withCount([
                'answers as correct_answers_count' => function ($query) {
                    // حل مشکل ارور ۵۰۰: تغییر ریلیشن از questionOption به option
                    $query->whereHas('option', function ($q) {
                        $q->where('is_correct', true);
                    });
                }
            ])
            ->get()
            ->map(function ($attempt) {

                $timeSpent = 'نامشخص';

                // ۳. منطقِ تشخیص وضعیت زنده کاربر (کی الان داره آزمون میده / کی تموم کرده)
                if ($attempt->started_at) {
                    if ($attempt->status === 'in_progress') {
                        // کاربر الان آنلاینه و داره آزمون میده؛ تفاضل زمان شروع تا همین ثانیه رو حساب میکنیم
                        $durationInSeconds = $attempt->started_at->diffInSeconds(now());
                        $minutes = floor($durationInSeconds / 60);
                        $seconds = $durationInSeconds % 60;
                        $timeSpent = "در حال آزمون ({$minutes} دقیقه و {$seconds} ثانیه گذشته)";
                    } elseif ($attempt->finished_at) {
                        // کاربر آزمون رو فرستاده و تموم کرده
                        $durationInSeconds = $attempt->started_at->diffInSeconds($attempt->finished_at);
                        $minutes = floor($durationInSeconds / 60);
                        $seconds = $durationInSeconds % 60;
                        $timeSpent = "{$minutes} دقیقه و {$seconds} ثانیه";
                    }
                }

                // ۴. ساختاردهی اطلاعات دقیق هر شرکت‌کننده
                return [
                    'attempt_id' => $attempt->id,
                    'user_id' => $attempt->user->id,
                    'mobile' => $attempt->user->mobile,
                    'status' => $attempt->status, // مقدار خام دیتابیس (in_progress, submitted و...)
                    'status_text' => match ($attempt->status) {
                        'in_progress' => 'هم‌اکنون در حال آزمون (آنلاین)',
                        'submitted' => 'پایان یافته (در انتظار تصحیح)',
                        'graded' => 'تصحیح شده',
                        'missed' => 'غایب',
                        default => 'نامشخص'
                    },
                    'score' => $attempt->score,
                    'correct_answers' => $attempt->correct_answers_count, // تعداد سوالاتی که درست جواب داده
                    'time_spent' => $timeSpent, // مدت زمانی که بابت آزمون صرف کرده یا در حال صرف کردنه
                    'started_at' => $attempt->started_at?->toIso8601String(),
                    'finished_at' => $attempt->finished_at?->toIso8601String(),
                ];
            });

        // ۵. بازگرداندن پاسخ نهایی به صورت JSON
        return response()->json([
            'success' => true,
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'total_questions' => $exam->questions_count,
                'duration_minutes' => $exam->duration_minutes,
            ],
            'takers' => $takers
        ]);
    }
}
