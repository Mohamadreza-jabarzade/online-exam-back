<?php

namespace App\Http\Controllers\ExamManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ExamStoreRequest;
use App\Http\Requests\Dashboard\ExamUpdateRequest;
use App\Models\Exam;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    public function index(): JsonResponse
    {
        if (!auth()->user()->can_create_exam) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی ساخت یا مشاهده آزمون را ندارید.'
            ], 403);
        }

        $query = auth()->user()->createdExams()->withCount(['questions', 'attempts']);

        $statusParam = request()->query('status');

        if ($statusParam) {
            $statuses = explode(',', $statusParam);
            $allowedStatuses = ['draft', 'closed', 'published', 'in_progress'];
            $validStatuses = array_intersect($statuses, $allowedStatuses);

            if (!empty($validStatuses)) {
                $query->whereIn('status', $validStatuses);
            }
        }

        $exams = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $exams
        ], 200);
    }

    public function store(ExamStoreRequest $request): JsonResponse
    {
        if (!auth()->user()->can_create_exam) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی ساخت آزمون را ندارید.'
            ], 403);
        }

        $data = $request->validated();

        $data['start_time'] = Carbon::parse($data['start_time'])->timezone('Asia/Tehran');
        $data['end_time'] = $data['start_time']->copy()->addMinutes((int) $data['duration_minutes']);
        $data['published_at'] = now();

        $exam = auth()->user()->createdExams()->create($data);

        $syncData = [];
        foreach ($data['questions'] as $index => $questionId) {
            $syncData[$questionId] = ['sort_order' => $index + 1];
        }
        $exam->questions()->sync($syncData);

        // لینک از env خوانده می‌شود
        $exam->exam_link = rtrim(config('app.frontend_url'), '/') . '/take-exam/' . $exam->uuid;

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت ایجاد شد.',
            'data' => $exam
        ], 201);
    }

    public function show(Exam $exam): JsonResponse
    {
        if ($exam->creator_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی مشاهده آزمون دیگران را ندارید.'
            ], 403);
        }
        $exam->load('questions');
        return response()->json([
            'success' => true,
            'data' => $exam
        ], 200);
    }

    public function update(ExamUpdateRequest $request, Exam $exam): JsonResponse
    {
        if ($exam->creator_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه ویرایش این آزمون را ندارید.'
            ], 403);
        }

        $data = $request->validated();
        if (isset($data['duration_minutes'])) {
            $data['start_time'] = Carbon::parse($data['start_time'])->timezone('Asia/Tehran');
        }
        if (isset($data['duration_minutes'])) {
            $data['end_time'] = $exam->start_time->copy()->addMinutes((int) $data['duration_minutes']);
        } else {
            $data['end_time'] = $exam->start_time->copy()->addMinutes((int) $exam->duration_minutes);
        }

        $exam->update($data);

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت به‌روزرسانی شد.',
            'data' => $exam
        ], 200);
    }

    public function destroy(Exam $exam): JsonResponse
    {
        if ($exam->creator_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه حذف این آزمون را ندارید.'
            ], 403);
        }

        $deleted = $exam->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'آزمون با موفقیت حذف شد.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'خطا در حذف آزمون. لطفا دوباره تلاش کنید.'
        ], 500);
    }
}
