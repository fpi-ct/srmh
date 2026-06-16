<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'care_status' => ['required', Rule::in(['stable', 'monitoring', 'critical'])],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
