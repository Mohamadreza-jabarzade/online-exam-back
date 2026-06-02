<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendOtpRequest extends FormRequest
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
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'mobile' => 'شماره موبایل'
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
            'mobile' => preg_replace('/[^0-9]/', '', $this->mobile)
        ]);
    }
}
