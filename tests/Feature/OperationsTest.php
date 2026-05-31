<?php

use App\Jobs\LogProjectActivity;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('reports a healthy status when the database is reachable', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('services.database', 'ok');
});

it('dispatches a background job when a project is created', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/projects', ['name' => 'Queued project'])
        ->assertCreated();

    Queue::assertPushed(LogProjectActivity::class);
});

it('returns cached user stats', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Task::factory(4)->for($project)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stats')
        ->assertOk()
        ->assertJsonPath('data.projects', 1)
        ->assertJsonPath('data.tasks', 4);
});
