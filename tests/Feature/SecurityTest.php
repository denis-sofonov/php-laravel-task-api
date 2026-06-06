<?php

use App\Models\User;

it('rate limits the login endpoint after too many attempts', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    // The first 5 attempts are within the limit (return 422 — wrong password).
    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    // The sixth hits the limiter -> 429 Too Many Requests.
    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

it('returns a clean 404 without leaking the model class', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/projects/999999')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Resource not found.']);
});
