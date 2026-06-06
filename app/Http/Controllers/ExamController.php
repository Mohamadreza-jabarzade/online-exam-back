<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dashborad\ExamStoreRequest;
use App\Http\Requests\Dashborad\ExamUpdateRequest;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // چک کردن دسترسی بر اساس فیلد boolean کاربر
        if (!auth()->user()->can_create_exam) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی ساخت یا مشاهده آزمون را ندارید.'
            ], 403);
        }

        // استفاده از Relation برای تمیزی بیشتر کد
        $exams = auth()->user()->createdExams()->get();

        return response()->json([
            'success' => true,
            'data' => $exams
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExamStoreRequest $request): JsonResponse
    {
        // دریافت دیتای تمیز
        $data = $request->validated();

        // ایجاد آزمون برای کاربر لاگین شده
        $exam = auth()->user()->createdExams()->create(array_merge($data, [
            'status' => 'draft' // همیشه آزمون در ابتدا پیش‌نویس است
        ]));

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت ایجاد شد.',
            'data' => $exam
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Exam $exam): JsonResponse
    {
        // چک کردن مالکیت آزمون
        if ($exam->creator_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی مشاهده آزمون دیگران را ندارید.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $exam
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExamUpdateRequest $request, Exam $exam): JsonResponse
    {
        // چک کردن دسترسی
        if ($exam->creator_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه ویرایش این آزمون را ندارید.'
            ], 403);
        }

        // دریافت دیتای تایید شده
        $data = $request->validated();

        // آپدیت آزمون
        $exam->update($data);

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت به‌روزرسانی شد.',
            'data' => $exam
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam): JsonResponse
    {
        // جلوگیری از حذف آزمون‌های دیگران (باگ امنیتی برطرف شد)
        if ($exam->creator_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه حذف این آزمون را ندارید.'
            ], 403);
        }

        // حذف آزمون
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
        ], 500); // 500 یعنی خطای سرور
    }
}
