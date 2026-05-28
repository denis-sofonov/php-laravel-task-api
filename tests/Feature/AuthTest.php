<?php

use App\Models\User;

it('registers a new user and returns a token', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'created_at'],
            'token',
        ]);

    $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
});

it('rejects registration with invalid data', function () {
    $this->postJson('/api/v1/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/register', [
        'name' => 'Bob',
        'email' => 'taken@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertJsonValidationErrors('email');
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ])->assertOk()->assertJsonStructure(['user', 'token']);
});

it('rejects login with a wrong password', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('blocks the profile endpoint without a token', function () {
    $this->getJson('/api/v1/user')->assertUnauthorized();
});

it('revokes the token on logout', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $token = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ])->json('token');

    // С действующим токеном logout проходит.
    $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

    // Токен физически удалён из базы.
    expect($user->tokens()->count())->toBe(0);

    // Сбрасываем закешированный в guard'е этого теста стейт авторизации,
    // иначе повторный запрос увидит "старого" пользователя из памяти.
    $this->app['auth']->forgetGuards();

    // Тот же токен после logout уже не работает.
    $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
});
