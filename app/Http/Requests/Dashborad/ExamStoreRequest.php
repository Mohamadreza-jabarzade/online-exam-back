<?php

namespace App\Http\Requests\Dashborad;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExamStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'show_result' => 'boolean',
            'show_correct_answers' => 'boolean',
            'random_questions' => 'boolean',
            'random_options' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان آزمون الزامی است.',
            'title.max' => 'عنوان آزمون نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'show_result.integer' => 'زمان آزمون باید به دقیقه و به صورت عدد باشد.',
            'show_result.boolean'          => 'مقدار نمایش نتیجه باید true یا false باشد.',
            'show_correct_answers.boolean' => 'مقدار نمایش پاسخ‌های صحیح باید true یا false باشد.',
            'random_questions.boolean'     => 'مقدار ترتیب تصادفی سوالات باید true یا false باشد.',
            'random_options.boolean'       => 'مقدار ترتیب تصادفی گزینه‌ها باید true یا false باشد.',
        ];
    }

    // این متد باعث میشه به جای ریدایرکت، خطای JSON برگرده
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'خطا در اعتبارسنجی داده‌ها',
            'errors' => $validator->errors()
        ], 422));
    }
}
