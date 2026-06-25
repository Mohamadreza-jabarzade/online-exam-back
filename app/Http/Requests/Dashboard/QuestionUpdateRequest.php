<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuestionUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string',
            'type' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'content.string' => 'سوال باید به صورت متنی باشد',
            'type.string' => 'نوع سوال باید متنی باشد',
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
