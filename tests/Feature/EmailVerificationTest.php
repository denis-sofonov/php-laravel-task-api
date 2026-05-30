<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('sends a verification email on registration', function () {
    Notification::fake();

    $this->postJson('/api/v1/register', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertCreated();

    $user = User::where('email', 'alice@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('verifies the email via a signed url', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->getJson($url)->assertOk();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects verification with an invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('wrong@example.com'),
    ]);

    $this->getJson($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email to an authenticated user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/email/verification-notification')
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
});
