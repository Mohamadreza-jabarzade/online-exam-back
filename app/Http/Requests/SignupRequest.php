<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:50',
            'family' => 'required|string|min:2|max:50'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است',
            'name.min' => 'نام باید حداقل ۲ حرف باشد',
            'name.max' => 'نام نباید بیشتر از ۵۰ حرف باشد',
            'family.required' => 'نام خانوادگی الزامی است',
            'family.min' => 'نام خانوادگی باید حداقل ۲ حرف باشد',
            'family.max' => 'نام خانوادگی نباید بیشتر از ۵۰ حرف باشد'
        ];
    }
}
