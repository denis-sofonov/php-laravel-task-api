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
     * Step 1: request a password reset link.
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        // Same response whether or not the email exists, so the endpoint can't be
        // used to discover which addresses are registered.
        if ($status === PasswordBroker::RESET_LINK_SENT || $status === PasswordBroker::INVALID_USER) {
            return response()->json(['message' => 'If the email exists, a reset link has been sent.']);
        }

        throw ValidationException::withMessages(['email' => [__($status)]]);
    }

    /**
     * Step 2: set a new password using the token from the email.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // The 'hashed' cast hashes the password on save.
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
