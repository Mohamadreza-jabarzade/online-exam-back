<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/|size:11',
            'code' => 'required|string|size:4'
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست',
            'mobile.size' => 'شماره موبایل باید ۱۱ رقم باشد',
            'code.required' => 'کد تایید الزامی است',
            'code.size' => 'کد تایید باید ۴ رقم باشد'
        ];
    }

    /**
     * بازنویسی متد مدیریت خطای ولیدیشن
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'خطا در اعتبارسنجی داده‌های ارسالی', // پیام فارسی دلخواه شما
            'errors' => $validator->errors()
        ], 422));
    }
}
