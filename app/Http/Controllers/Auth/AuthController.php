<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Регистрация нового пользователя и выдача токена.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // validated() возвращает только прошедшие валидацию поля — безопаснее,
        // чем брать весь $request. Пароль захешируется автоматически (cast 'hashed').
        $user = User::create($request->validated());

        $token = $user->createToken($this->tokenName($request))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Вход: проверяем учётные данные и выдаём новый токен.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // Одинаковое сообщение для "нет пользователя" и "неверный пароль" —
        // чтобы не подсказывать атакующему, какие email зарегистрированы.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $token = $user->createToken($this->tokenName($request))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Текущий авторизованный пользователь (по токену).
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Выход: отзываем только тот токен, которым сделан текущий запрос.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Имя токена — из тела запроса (device_name) или по умолчанию.
     */
    private function tokenName(Request $request): string
    {
        return $request->input('device_name', 'api');
    }
}
