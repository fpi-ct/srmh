<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'access_code' => ['required', 'string', 'max:50', 'unique:users,access_code'],
            'full_name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'role_label' => ['required', 'string', 'max:100'],
            'faculties' => ['nullable', 'string', 'max:500'],
        ];
    }
}
