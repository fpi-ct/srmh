<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_code' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'access_code.required' => 'Vui lòng nhập mã truy cập.',
        ];
    }
}
