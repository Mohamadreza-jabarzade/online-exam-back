<?php

namespace App\Http\Controllers\ExamManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\BankStoreRequest;
use App\Http\Requests\Dashboard\BankUpdateRequest;
use App\Http\Requests\Dashboard\QuestionStoreRequest;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use Illuminate\Http\JsonResponse;

class QuestionBankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $banks = auth()->user()->questionBanks()
            ->withCount('questions')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $banks,
        ], 200);
    }

    public function bankQuestions(QuestionBank $bank)
    {
        if ($bank->user_id !== auth()->id() && !$bank->is_public) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی مشاهده بانک خصوصی دیگران را ندارید.'
            ], 403);
        }
        $questions = $bank->questions()->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $questions
        ], 200);
    }

    public function bankQuestionStore(QuestionStoreRequest $request, QuestionBank $bank): JsonResponse
    {
        $data = $request->validated();

        // گزینه‌ها را جدا می‌کنیم
        $options = $data['options'];
        unset($data['options']);

        // اتصال سوال به بانک
        $data['question_bank_id'] = $bank->id;

        // ایجاد سوال
        $question = auth()->user()
            ->createdQuestions()
            ->create($data);

        // ایجاد گزینه‌ها
        foreach ($options as $index => $option) {
            $question->options()->create([
                'content' => $option['content'],
                'is_correct' => $option['is_correct'] ?? false,
                'sort_order' => $index + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت ایجاد شد.',
            'data' => $question->load('options'),
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BankStoreRequest $request): JsonResponse
    {
        // دریافت دیتای تمیز
        $data = $request->validated();

        // ایجاد آزمون برای کاربر لاگین شده
        $bank = auth()->user()->questionBanks()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'بانک سوال شما با موفقیت ایجاد شد.',
            'data' => $bank
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(QuestionBank $bank): JsonResponse
    {
        if ($bank->user_id !== auth()->id() && !$bank->is_public) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی مشاهده بانک خصوصی دیگران را ندارید.'
            ], 403);
        }

        $bank->load('questions.options');

        return response()->json([
            'success' => true,
            'data' => $bank,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BankUpdateRequest $request, QuestionBank $bank): JsonResponse
    {
        // چک کردن دسترسی
        if ($bank->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه ویرایش این بانک سوال را ندارید.'
            ], 403);
        }

        // دریافت دیتای تایید شده
        $data = $request->validated();

        // آپدیت بانک
        $bank->update($data);

        return response()->json([
            'success' => true,
            'message' => 'بانک سوال با موفقیت به‌روزرسانی شد.',
            'data' => $bank
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuestionBank $bank): JsonResponse
    {
        // جلوگیری از حذف آزمون‌های دیگران (باگ امنیتی برطرف شد)
        if ($bank->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه حذف این بانک سوال را ندارید.'
            ], 403);
        }

        // حذف آزمون
        $deleted = $bank->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'بانک سوال با موفقیت حذف شد.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'خطا در حذف بانک سوال. لطفا دوباره تلاش کنید.'
        ], 500); // 500 یعنی خطای سرور
    }
}
