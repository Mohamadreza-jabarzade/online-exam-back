<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExamUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'sometimes|integer|min:1',
            'show_result' => 'boolean',
            'show_correct_answers' => 'boolean',
            'random_questions' => 'boolean',
            'random_options' => 'boolean',
            'status' => 'sometimes|in:draft,published,closed',
            'start_time' => 'required',
        ];
    }

    /**
     * تعریف پیام‌های خطای فارسی
     */
    public function messages(): array
    {
        return [
            'start_time.required'       => 'زمان شروع ازمون را وارد کنید',
            'title.required'         => 'عنوان آزمون نمی‌تواند خالی باشد.',
            'title.max'              => 'عنوان آزمون نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'duration_minutes.integer' => 'زمان آزمون باید یک عدد صحیح باشد.',
            'duration_minutes.min'     => 'زمان آزمون حداقل باید ۱ دقیقه باشد.',
            'status.in'              => 'وضعیت انتخاب شده نامعتبر است (مقادیر مجاز: پیش‌نویس، منتشر شده، بسته شده).',
            'show_result.boolean'          => 'مقدار نمایش نتیجه باید true یا false باشد.',
            'show_correct_answers.boolean' => 'مقدار نمایش پاسخ‌های صحیح باید true یا false باشد.',
            'random_questions.boolean'     => 'مقدار ترتیب تصادفی سوالات باید true یا false باشد.',
            'random_options.boolean'       => 'مقدار ترتیب تصادفی گزینه‌ها باید true یا false باشد.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'خطا در اعتبارسنجی داده‌ها',
            'errors' => $validator->errors() // اینجا لاراول به صورت خودکار پیام‌های فارسی بالا رو جایگزین می‌کنه
        ], 422));
    }
}
