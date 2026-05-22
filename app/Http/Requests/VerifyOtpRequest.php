<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
}
