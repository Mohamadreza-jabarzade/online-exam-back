<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\User;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // همه می‌توانند استفاده کنند
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mobile' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                'size:11',
                'exists:users,mobile' // بررسی وجود شماره در دیتابیس
            ],
            'code' => [
                'required',
                'string',
                'size:4',
                'regex:/^[0-9]{4}$/' // فقط اعداد 4 رقمی
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'mobile' => 'شماره موبایل',
            'code' => 'کد تایید'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // پیام‌های مربوط به فیلد mobile
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.string' => 'شماره موبایل باید به صورت متن وارد شود',
            'mobile.size' => 'شماره موبایل باید دقیقاً 11 رقم باشد',
            'mobile.exists' => 'شماره موبایل وارد شده در سیستم ثبت نشده است. لطفاً ابتدا کد تایید را دریافت کنید',

            // پیام‌های مربوط به فیلد code
            'code.required' => 'کد تایید الزامی است',
            'code.string' => 'کد تایید باید به صورت متن وارد شود',
            'code.size' => 'کد تایید باید دقیقاً 4 رقم باشد',
            'code.regex' => 'کد تایید باید فقط شامل اعداد 0 تا 9 باشد'
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'خطا در اعتبارسنجی اطلاعات ارسالی',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // پاک کردن فاصله و کاراکترهای اضافی از شماره موبایل
        $this->merge([
            'mobile' => preg_replace('/[^0-9]/', '', $this->mobile),
            'code' => preg_replace('/[^0-9]/', '', $this->code) // فقط اعداد از کد
        ]);
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // اعتبارسنجی سفارشی اضافی در صورت نیاز
            $mobile = $this->input('mobile');
            $code = $this->input('code');

            // بررسی اینکه آیا کد منقضی شده یا نه (اختیاری - در کنترلر هم بررسی می‌شود)
            $user = User::where('mobile', $mobile)->first();

            if ($user && $user->otp_code && $user->otp_code != $code) {
                $validator->errors()->add('code', 'کد تایید وارد شده اشتباه است');
            }

            if ($user && $user->otp_expires_at && now()->isAfter($user->otp_expires_at)) {
                $validator->errors()->add('code', 'کد تایید منقضی شده است. لطفاً کد جدید دریافت کنید');
            }
        });
    }
}
