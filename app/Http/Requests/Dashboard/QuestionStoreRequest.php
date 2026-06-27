<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuestionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string',
            'type' => 'required|string',
            'question_bank_id' => 'nullable|numeric',
            'options' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'وارد کردن سوال الزامی است.',
            'content.string' => 'وارد کردن سوال الزامی است.',
            'options.array' => 'گزینه های سوال باید ارایه باشد.',
            'type.required' => 'وارد کردن نوع سوال الزامی است',
            'type.string' => 'وارد کردن نوع سوال الزامی است',
            'question_bank_id.numeric' => 'فرمت ایدی بانک سوال به صورت عددی وارد نشده',
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
