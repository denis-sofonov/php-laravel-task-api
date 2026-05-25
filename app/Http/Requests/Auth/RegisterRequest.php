<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Регистрация доступна любому гостю, поэтому авторизация не нужна.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации тела запроса.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            // 'confirmed' требует поле password_confirmation с тем же значением;
            // Password::defaults() — централизованные правила силы пароля.
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
