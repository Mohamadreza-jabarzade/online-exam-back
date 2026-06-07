<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BankStoreRequest extends FormRequest
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
            'is_public' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان بانک سوال الزامی است.',
            'title.max' => 'عنوان بانک سوال نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'is_public.boolean' => 'مقدار عمومی یا خصوصی بودن بانک سوال باید true یا false باشد.',
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
