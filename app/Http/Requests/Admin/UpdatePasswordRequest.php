<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => '请输入当前密码',
            'password.required' => '请输入新密码',
            'password.confirmed' => '两次输入的新密码不一致',
        ];
    }
}
