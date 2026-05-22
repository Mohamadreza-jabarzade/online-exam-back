<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/|max:11'
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.regex' => 'فرمت شماره موبایل صحیح نیست (مثال: 09011111111)',
            'mobile.max' => 'شماره موبایل باید 11 رقم باشد'
        ];
    }
}
