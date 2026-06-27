<?php

namespace App\Http\Requests\Admin;

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
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '请输入邮箱',
            'email.email' => '邮箱格式不正确',
            'password.required' => '请输入密码',
            'password.min' => '密码至少 6 位',
        ];
    }
}
