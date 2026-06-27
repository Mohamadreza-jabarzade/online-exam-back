<?php

namespace App\Http\Controllers\ExamManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\QuestionStoreRequest;
use App\Http\Requests\Dashboard\QuestionUpdateRequest;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $questions = auth()->user()->createdQuestions()->get();

        return response()->json([
            'success' => true,
            'data' => $questions
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuestionStoreRequest $request)
    {
        $data = $request->validated();

        // گزینه‌ها را جدا می‌کنیم
        $options = $data['options'];
        unset($data['options']);
        $default_bank = QuestionBank::where('title' , 'بانک پیشفرض')->first();
        if ($default_bank){
            $bank = $default_bank;
        }else{
            $bank = QuestionBank::create([
                'user_id' => auth()->user()->id,
                'title' => 'بانک پیشفرض',
                'description' => 'سوالات بدون بانک شما داخل این بانک ذخیره می شوند.',
                'is_public' => false
            ]);
        }
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
     * Display the specified resource.
     */
    public function show(Question $question) : JsonResponse
    {
        if ($question->questionBank()->first()->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی مشاهده سوالات دیگران را ندارید.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $question
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QuestionUpdateRequest $request, Question $question)
    {
        // چک کردن دسترسی
        if ($question->questionBank()->first()->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه ویرایش این سوال را ندارید.'
            ], 403);
        }

        // دریافت دیتای تایید شده
        $data = $request->validated();

        // آپدیت بانک
        $question->update($data);

        return response()->json([
            'success' => true,
            'message' => ' سوال با موفقیت به‌روزرسانی شد.',
            'data' => $question
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question) : JsonResponse
    {
        if ($question->questionBank()->first()->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'شما اجازه حذف این سوال را ندارید.'
            ], 403);
        }

        $deleted = $question->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => ' سوال با موفقیت حذف شد.'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'خطا در حذف سوال. لطفا دوباره تلاش کنید.'
        ], 500);
    }
}
