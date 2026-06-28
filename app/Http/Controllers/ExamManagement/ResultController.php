<?php

namespace App\Http\Controllers\ExamManagement;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function stats(): JsonResponse
    {
        $user = auth()->user();

        // ۱. چک کردن دسترسی بیسیک طراح/مدیر
        if (!$user->can_create_exam) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی به این بخش را ندارید.'
            ], 403);
        }

        // ۲. تعداد کل آزمون‌های ساخته شده توسط این کاربر
        $totalExams = $user->createdExams()->count();

        // ۳. تعداد کل سوالات طراحی شده توسط این کاربر (بانک سوالات شخصی)
        $totalQuestions = $user->createdQuestions()->count();

        // ۴. تعداد کل اتمپت‌های «در انتظار تصحیح» برای تمام آزمون‌های این طراح
        $pendingGradingCount = ExamAttempt::where('status', 'submitted')
            ->whereHas('exam', function ($query) use ($user) {
                $query->where('creator_id', $user->id);
            })
            ->count();

        // ۵. آزمون‌های پیش رو و پیش‌نویس (فقط ۳ تای آخر)
        // شامل آزمون‌های پیش‌نویس (draft) یا منتشرشده‌هایی (published) که تاریخ شروع آن‌ها در آینده است
        $upcomingExams = $user->createdExams()
            ->select('id', 'title', 'start_time', 'status') // فقط فیلدهای درخواستی شما
            ->where(function ($query) {
                $query->where('status', 'draft')
                    ->orWhere(function ($q) {
                        $q->where('status', 'published')
                            ->where('start_time', '>', now());
                    });
            })
            ->latest()
            ->limit(3) // محدود به ۳ تای آخر
            ->get();

        // ۶. آزمون‌های تصحیح‌نشده (فقط ۵ تای آخر)
        // شامل آزمون‌های بسته‌شده‌ای (closed) که حداقل یک تلاش تصحیح‌نشده (submitted) دارند
        $ungradedExams = $user->createdExams()
            ->select('id', 'title', 'end_time') // شامل عنوان و تاریخ پایان آزمون
            ->where('status', 'closed')
            ->whereHas('attempts', function ($query) {
                $query->where('status', 'submitted');
            })
            ->withCount([
                'attempts as total_attempts_count', // تعداد کل کسانی که آزمون داده‌اند (Y)
                'attempts as graded_attempts_count' => function ($query) {
                    $query->where('status', 'graded'); // تعداد کسانی که تصحیح شده‌اند (X)
                },
                'attempts as pending_attempts_count' => function ($query) {
                    $query->where('status', 'submitted'); // تعداد برگه‌های باقی‌مانده
                }
            ])
            ->latest()
            ->limit(5) // محدود به ۵ تای آخر
            ->get();

        // ۷. بازگرداندن پاسخ نهایی آمار و لیست‌ها به صورت یک‌جا
        return response()->json([
            'success' => true,
            'stats' => [
                'total_exams' => $totalExams,
                'total_questions' => $totalQuestions,
                'pending_grading' => $pendingGradingCount,
            ],
            'upcoming_exams' => $upcomingExams,
            'ungraded_exams' => $ungradedExams
        ], 200);
    }
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

                $timeSpent = null;

                // ۳. منطقِ تشخیص وضعیت زنده کاربر (کی الان داره آزمون میده / کی تموم کرده)
                if ($attempt->started_at) {
                    if ($attempt->status === 'in_progress') {
                        // کاربر الان آنلاینه؛ تفاضل زمان شروع تا همین لحظه بر حسب کل ثانیه‌ها به صورت عدد صحیح
                        $timeSpent = (int) $attempt->started_at->diffInSeconds(now());
                    } elseif ($attempt->finished_at) {
                        // کاربر آزمون رو تموم کرده؛ تفاضل زمان شروع تا پایان بر حسب کل ثانیه‌ها به صورت عدد صحیح
                        $timeSpent = (int) $attempt->started_at->diffInSeconds($attempt->finished_at);
                    }
                }

                // ۴. ساختاردهی اطلاعات دقیق هر شرکت‌کننده
                return [
                    'attempt_id' => $attempt->id,
                    'user_id' => $attempt->user->id,
                    'mobile' => $attempt->user->mobile,
                    'status' => $attempt->status, // مقدار خام دیتابیس (in_progress, submitted و...)
                    'correct_answers' => $attempt->correct_answers_count, // تعداد سوالاتی که درست جواب داده
                    'time_spent' => $timeSpent, // خروجی به صورت عدد صحیح تام (مثلاً 90) یا null
                    'started_at' => $attempt->started_at?->toIso8601String(),
                    'finished_at' => $attempt->finished_at?->toIso8601String(),
                ];
            });

        // ۵. بازگرداندن پاسخ نهایی به صورت JSON
        return response()->json([
            'success' => true,
            'data' => [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'total_questions' => $exam->questions_count,
                    'duration_minutes' => $exam->duration_minutes,
                ],
                'takers' => $takers,
            ],
        ], 200);
    }
}
