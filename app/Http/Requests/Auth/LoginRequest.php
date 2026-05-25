<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Вход доступен любому гостю.
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            // Необязательное имя устройства — попадёт в название токена.
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
