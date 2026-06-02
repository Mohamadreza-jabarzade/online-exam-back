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
        return true;
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
                'exists:users,mobile'
            ],
            'code' => [
                'required',
                'string',
                'size:4',
                'regex:/^[0-9]{4}$/'
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
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.string' => 'شماره موبایل باید به صورت متن وارد شود',
            'mobile.regex' => 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد',
            'mobile.size' => 'شماره موبایل باید دقیقاً 11 رقم باشد',
            'mobile.exists' => 'شماره موبایل وارد شده در سیستم ثبت نشده است',
            'code.required' => 'کد تایید الزامی است',
            'code.string' => 'کد تایید باید به صورت متن وارد شود',
            'code.size' => 'کد تایید باید دقیقاً 4 رقم باشد',
            'code.regex' => 'کد تایید باید فقط شامل اعداد 0 تا 9 باشد'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        // گرفتن اولین پیام خطا
        $firstErrorMessage = null;

        foreach ($errors as $field => $messages) {
            if (!empty($messages)) {
                $firstErrorMessage = $messages[0];
                break;
            }
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $firstErrorMessage ?? 'خطا در اعتبارسنجی اطلاعات'
        ], 422));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => preg_replace('/[^0-9]/', '', $this->mobile),
            'code' => preg_replace('/[^0-9]/', '', $this->code)
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // اگر قبلاً خطایی وجود دارد، نیازی به بررسی نیست
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mobile = $this->input('mobile');
            $code = $this->input('code');

            $user = User::where('mobile', $mobile)->first();

            if ($user && $user->otp_code && $user->otp_code != $code) {
                $validator->errors()->add('code', 'کد تایید وارد شده اشتباه است');
                return;
            }

            if ($user && $user->otp_expires_at && now()->isAfter($user->otp_expires_at)) {
                $validator->errors()->add('code', 'کد تایید منقضی شده است. لطفاً کد جدید دریافت کنید');
                return;
            }
        });
    }
}
