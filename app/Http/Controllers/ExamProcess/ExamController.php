<?php

namespace App\Http\Controllers\ExamProcess;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    /**
     * صفحه وضعیت آزمون برای کاربر
     */
    public function show(Exam $exam): JsonResponse
    {
        $exam->loadCount('questions');

        $attempt = $exam->attempts()
            ->where('user_id', auth()->id())
            ->withCount('answers')
            ->first();

        // آزمون هنوز شروع نشده است
        if (now()->lt($exam->start_time)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'not_started',
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'questions_count' => $exam->questions_count,
                    'duration_minutes' => $exam->duration_minutes,
                    'start_time' => $exam->start_time,
                    'expire_time' => $exam->end_time,
                ]
            ]);
        }

        // آزمون فعال است و کاربر هنوز شرکت نکرده است
        if (now()->between($exam->start_time, $exam->end_time) && !$attempt) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'can_start',
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'questions_count' => $exam->questions_count,
                    'duration_minutes' => $exam->duration_minutes,
                    'start_time' => $exam->start_time,
                    'expire_time' => $exam->end_time,
                ]
            ]);
        }

        // کاربر در حال حاضر مشغول پاسخ‌گویی به آزمون است
        if ($attempt && $attempt->status === 'in_progress' && now()->lte($exam->end_time)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'in_progress'
                ]
            ]);
        }

        // زمان آزمون به پایان رسیده و کاربر شرکت نکرده است
        if (now()->gt($exam->end_time) && !$attempt) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'missed',
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'message' => 'زمان آزمون به پایان رسیده است و شما در آزمون شرکت نکرده‌اید.'
                ]
            ]);
        }

        // کاربر آزمون را ارسال کرده یا آزمون تصحیح شده است
        return response()->json([
            'success' => true,
            'data' => [
                'status' => $attempt->status,
                'title' => $exam->title,
                'answered_questions' => $attempt->answers_count,
                'questions_count' => $exam->questions_count,
                'message' => match ($attempt->status) {
                    'graded' => 'آزمون تصحیح شده است.',
                    default => 'آزمون ثبت شده و در انتظار تصحیح است.',
                }
            ]
        ]);
    }

    /**
     * شروع فرآیند آزمون و ایجاد تلا‌ش جدید
     */
    public function start(Exam $exam): JsonResponse
    {
        // ۱. بررسی بازه زمانی مجاز آزمون
        if (!now()->between($exam->start_time, $exam->end_time)) {
            return response()->json([
                'success' => false,
                'message' => 'در حال حاضر امکان شروع این آزمون وجود ندارد.'
            ], 403);
        }

        // ۲. بررسی عدم شرکت قبلی کاربر در این آزمون
        $existingAttempt = $exam->attempts()
            ->where('user_id', auth()->id())
            ->first();

        if ($existingAttempt) {
            return response()->json([
                'success' => false,
                'message' => 'شما قبلاً این آزمون را شروع کرده‌اید.'
            ], 400);
        }

        // ۳. ایجاد نشست آزمون جدید
        $exam->attempts()->create([
            'user_id' => auth()->id(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت شروع شد.'
        ]);
    }

    /**
     * دریافت سوالات و وضعیت جلسه جاری آزمون
     */
    public function attempt(Exam $exam): JsonResponse
    {
        $attempt = $exam->attempts()
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه آزمون فعالی برای شما یافت نشد.'
            ], 404);
        }

        // زمان آزمون به پایان رسیده است
        if (now()->gte($exam->end_time)) {

            $attempt->update([
                'status' => 'submitted',
                'finished_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'finished',
                'message' => 'زمان آزمون به پایان رسیده است.'
            ], 403);
        }

        // زمان باقی‌مانده تا پایان آزمون (ثانیه)
        $timeLeft = now()->diffInSeconds($exam->end_time);

        // سوالات آزمون
        $questions = $exam->questions()
            ->with('options')
            ->get();

        // پاسخ‌های ذخیره شده کاربر
        $answers = $attempt->answers()
            ->get()
            ->keyBy('question_id');

        $formattedQuestions = $questions->map(function ($question) use ($answers) {

            $answer = $answers->get($question->id);

            return [
                'id' => $question->id,
                'content' => $question->content,

                'user_answer' => $answer?->question_option_id,

                'options' => $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'content' => $option->content,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'exam' => [
                    'title' => $exam->title,
                    'time_left' => $timeLeft,
                ],
                'questions' => $formattedQuestions,
            ]
        ]);
    }

    /**
     * ثبت یا به‌روزرسانی پاسخ یک سوال
     */
    public function saveAnswer(Request $request, Exam $exam): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'nullable|exists:question_options,id',
            'is_flagged' => 'boolean'
        ]);

        $attempt = $exam->attempts()
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه آزمون فعالی یافت نشد یا زمان آزمون به پایان رسیده است.'
            ], 404);
        }

        // درج پاسخ یا به‌روزرسانی آن
        $attempt->answers()->updateOrCreate(
            ['question_id' => $request->question_id],
            [
                'question_option_id' => $request->answer_id,
                'is_flagged' => $request->is_flagged ?? false
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'پاسخ با موفقیت ذخیره شد.'
        ]);
    }

    /**
     * ثبت نهایی و پایان آزمون
     */
    public function finish(Exam $exam): JsonResponse
    {
        $attempt = $exam->attempts()
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'جلسه آزمون فعالی برای خاتمه دادن یافت نشد.'
            ], 404);
        }

        $attempt->update([
            'status' => 'submitted',
            'finished_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'آزمون شما با موفقیت به پایان رسید و ثبت شد.'
        ]);
    }
}
