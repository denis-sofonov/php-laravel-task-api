<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends a reset link for an existing email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
        ->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether an email exists', function () {
    $this->postJson('/api/v1/forgot-password', ['email' => 'nobody@example.com'])
        ->assertOk();
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['password' => 'OldPassword1!']);
    $token = Password::createToken($user);

    $this->postJson('/api/v1/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();

    // The new password works at login.
    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'NewPassword1!',
    ])->assertOk();
});

it('rejects a reset with an invalid token', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertStatus(422);
});
