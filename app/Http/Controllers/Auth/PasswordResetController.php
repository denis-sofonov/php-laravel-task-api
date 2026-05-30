<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Шаг 1: запросить ссылку для сброса пароля.
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        // Отвечаем одинаково независимо от существования email —
        // чтобы нельзя было выяснить, какие адреса зарегистрированы.
        if ($status === PasswordBroker::RESET_LINK_SENT || $status === PasswordBroker::INVALID_USER) {
            return response()->json(['message' => 'If the email exists, a reset link has been sent.']);
        }

        throw ValidationException::withMessages(['email' => [__($status)]]);
    }

    /**
     * Шаг 2: установить новый пароль по токену из письма.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // Cast 'hashed' захеширует пароль при сохранении.
                $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Password has been reset.']);
    }
}
