<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

it('lists tasks of a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Task::factory(3)->for($project)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}/tasks")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a task inside a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Write tests',
            'status' => TaskStatus::InProgress->value,
            'due_date' => '2026-07-01',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Write tests')
        ->assertJsonPath('data.status', 'in_progress');

    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'Write tests',
    ]);
});

it('defaults the status to todo when not provided', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/projects/{$project->id}/tasks", ['title' => 'No status'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'todo');
});

it('rejects an invalid status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Bad status',
            'status' => 'nonsense',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('updates a task', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create(['status' => TaskStatus::Todo]);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/tasks/{$task->id}", ['status' => TaskStatus::Done->value])
        ->assertOk()
        ->assertJsonPath('data.status', 'done');
});

it('deletes a task', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

it('forbids creating a task in a project owned by another user', function () {
    $project = Project::factory()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder, 'sanctum')
        ->postJson("/api/projects/{$project->id}/tasks", ['title' => 'hack'])
        ->assertForbidden();
});

it('forbids touching a task owned by another user', function () {
    $task = Task::factory()->for(Project::factory())->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder, 'sanctum')
        ->getJson("/api/tasks/{$task->id}")
        ->assertForbidden();
});
