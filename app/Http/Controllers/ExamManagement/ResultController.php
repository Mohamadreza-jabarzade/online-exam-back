<?php

namespace App\Http\Controllers\ExamManagement;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;

class ResultController extends Controller
{
    /**
     * دریافت نتیجه یک جلسه آزمون خاص
     */
    public function attemptResult(ExamAttempt $attempt): JsonResponse
    {
        $exam = $attempt->exam;

        if ($exam->creator_id !== auth()->id()) {
            return $this->errorResponse('شما دسترسی مشاهده این جلسه آزمون را ندارید.', 403);
        }

        // لود روابط مورد نیاز
        $attempt->load(['user', 'answers.option']);
        $exam->loadCount('questions');

        $userAnswers = $attempt->answers->keyBy('question_id');
        $correctAnswersCount = 0;

        $questions = $exam->questions()->with('options')->get()->map(function ($question) use ($userAnswers, &$correctAnswersCount) {
            $userAnswerId = $userAnswers->get($question->id)?->question_option_id;

            // بهینه‌سازی بررسی صحت پاسخ کاربر با کمک firstWhere
            $isCorrect = $userAnswerId && ($question->options->firstWhere('id', $userAnswerId)?->is_correct ?? false);

            if ($isCorrect) {
                $correctAnswersCount++;
            }

            return [
                'id'          => $question->id,
                'content'     => $question->content,
                'is_correct'  => $isCorrect,
                'user_answer' => $userAnswerId,
                'options'     => $question->options->map(fn($option) => [
                    'id'      => $option->id,
                    'content' => $option->content,
                    'correct' => $option->is_correct,
                ])->values(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'attempt' => [
                    'mobile'                => $attempt->user->mobile,
                    'start_time'            => $attempt->started_at?->toIso8601String(),
                    'end_time'              => $attempt->finished_at?->toIso8601String(),
                    'time_spent'            => $this->calculateTimeSpent($attempt),
                    'status'                => $attempt->status,
                    'correct_answers_count' => $correctAnswersCount,
                    'total_questions'       => $exam->questions_count,
                ],
                'exam' => [
                    'questions_count'  => $exam->questions_count,
                    'duration_minutes' => $exam->duration_minutes,
                    'status'           => $exam->status,
                ],
                'questions' => $questions,
            ],
        ]);
    }

    /**
     * دریافت آمار کلی داشبورد استاد/مدیر
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->can_create_exam) {
            return $this->errorResponse('شما دسترسی به این بخش را ندارید.', 403);
        }

        $pendingGradingCount = ExamAttempt::where('status', 'submitted')
            ->whereHas('exam', fn($query) => $query->where('creator_id', $user->id))
            ->count();

        $upcomingExams = $user->createdExams()
            ->select('id', 'title', 'start_time', 'status')
            ->where('status', 'draft')
            ->orWhere(fn($q) => $q->where('status', 'published')->where('start_time', '>', now()))
            ->latest()
            ->limit(3)
            ->get();

        $ungradedExams = $user->createdExams()
            ->select('id', 'title', 'end_time')
            ->where('status', 'closed')
            ->whereHas('attempts', fn($q) => $q->where('status', 'submitted'))
            ->withCount([
                'attempts as total_attempts_count',
                'attempts as graded_attempts_count' => fn($q) => $q->where('status', 'graded'),
                'attempts as pending_attempts_count' => fn($q) => $q->where('status', 'submitted')
            ])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success'        => true,
            'stats' => [
                'total_exams'     => $user->createdExams()->count(),
                'total_questions' => $user->createdQuestions()->count(),
                'pending_grading' => $pendingGradingCount,
            ],
            'upcoming_exams' => $upcomingExams,
            'ungraded_exams' => $ungradedExams
        ]);
    }

    /**
     * دریافت لیست نتایج تمام شرکت‌کنندگان یک آزمون
     */
    public function examResult(Exam $exam): JsonResponse
    {
        $exam->loadCount('questions');

        $takers = $exam->attempts()
            ->with('user')
            ->withCount(['answers as correct_answers_count' => fn($q) => $q->whereHas('option', fn($o) => $o->where('is_correct', true))])
            ->get()
            ->map(fn($attempt) => [
                'attempt_id'      => $attempt->id,
                'user_id'         => $attempt->user->id,
                'mobile'          => $attempt->user->mobile,
                'status'          => $attempt->status,
                'correct_answers' => $attempt->correct_answers_count,
                'time_spent'      => $this->calculateTimeSpent($attempt),
                'started_at'      => $attempt->started_at?->toIso8601String(),
                'finished_at'     => $attempt->finished_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'exam' => [
                    'id'               => $exam->id,
                    'title'            => $exam->title,
                    'total_questions'  => $exam->questions_count,
                    'duration_minutes' => $exam->duration_minutes,
                ],
                'takers' => $takers,
            ],
        ]);
    }

    /**
     * هلپر اختصاصی برای محاسبه زمان صرف شده (ثانیه) به صورت عدد صحیح
     */
    private function calculateTimeSpent(ExamAttempt $attempt): ?int
    {
        return $attempt->started_at
            ? (int) $attempt->started_at->diffInSeconds($attempt->finished_at ?? now())
            : null;
    }

    /**
     * هلپر اختصاصی برای بازگرداندن خطاهای یکسان
     */
    private function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}
