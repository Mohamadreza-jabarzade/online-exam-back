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
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // ۱. چک کردن دسترسی بر اساس فیلد boolean کاربر
        if (!auth()->user()->can_create_exam) {
            return response()->json([
                'success' => false,
                'message' => 'شما دسترسی ساخت یا مشاهده آزمون را ندارید.'
            ], 403);
        }

        // ۲. شروع کوئری و بهینه‌سازی شمارش سوالات و شرکت‌کنندگان برای جلوگیری از کندی سرعت (N+1 Problem)
        // با اضافه کردن attempts، فیلد attempts_count به خروجی اضافه شده و در صورت نبود اتمپت، مقدار 0 می‌گیرد
        $query = auth()->user()->createdExams()->withCount(['questions', 'attempts']);

        // ۳. گرفتن وضعیت از URL و اعمال فیلتر (پشتیبانی از تک وضعیت یا چند وضعیت جدا شده با کاما)
        $statusParam = request()->query('status');

        if ($statusParam) {
            $statuses = explode(',', $statusParam);

            // لیست وضعیت‌های مجاز و استاندارد در سیستم شما
            $allowedStatuses = ['draft', 'closed', 'published','in_progress'];

            // فیلتر کردن آرایه ورودی تا فقط مقادیر مجاز باقی بمانند (جلوگیری از مقدار دهی خرابکارانه یا اشتباه)
            $validStatuses = array_intersect($statuses, $allowedStatuses);

            // اگر بعد از فیلتر، آرایه خالی نبود، شرط whereIn اعمال می‌شود
            if (!empty($validStatuses)) {
                $query->whereIn('status', $validStatuses);
            }
        }

        // ۴. مرتب‌سازی بر اساس جدیدترین آزمون‌های ساخته شده و دریافت دیتا
        $exams = $query->latest()->get();

        // ۵. بازگرداندن پاسخ نهایی به صورت JSON
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
        $data = $request->validated();

        $data['start_time'] = Carbon::parse($data['start_time'])->timezone('Asia/Tehran');
        $data['end_time'] = $data['start_time']->copy()->addMinutes((int) $data['duration_minutes']);
        $data['published_at'] = \Illuminate\Support\now();

        // ۱. آزمون ساخته می‌شود (و UUID در اینجا خودکار تولید و ذخیره می‌شود)
        $exam = auth()->user()->createdExams()->create($data);

        // ۲. متصل کردن سوالات (طبق کدهای قبلی خودتان)
        $syncData = [];
        foreach ($data['questions'] as $index => $questionId) {
            $syncData[$questionId] = ['sort_order' => $index + 1];
        }
        $exam->questions()->sync($syncData);

        // ۳. تولید لینک و اضافه کردن آن به پاسخ
        // فرض کنیم فرانت‌اند شما آدرسی مثل نمونه زیر دارد:
        $exam->exam_link = "https://yourfrontend.com/take-exam/" . $exam->uuid;

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت ایجاد شد.',
            'data' => $exam // داخل این داتا، فیلد exam_link اضافه شده است
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
        $exam->load('questions');
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
        $data['start_time'] = Carbon::parse($data['start_time'])
            ->timezone('Asia/Tehran');
        if (isset($data['duration_minutes'])){
            $data['end_time'] = $data['start_time']
                ->copy()
                ->addMinutes((int) $data['duration_minutes']);
        }else{
            $data['end_time'] = $data['start_time']
                ->copy()
                ->addMinutes((int) $exam->duration_minutes);
        }

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
