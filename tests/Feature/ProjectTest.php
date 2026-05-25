<?php

use App\Models\Project;
use App\Models\User;

it('requires authentication', function () {
    $this->getJson('/api/projects')->assertUnauthorized();
});

it('lists only the projects of the authenticated user', function () {
    $user = User::factory()->create();
    Project::factory(2)->for($user)->create();
    Project::factory(3)->create(); // чужие проекты не должны попасть в выдачу

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/projects')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('creates a project owned by the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/projects', ['name' => 'New Project', 'description' => 'Desc'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'New Project');

    $this->assertDatabaseHas('projects', [
        'name' => 'New Project',
        'user_id' => $user->id,
    ]);
});

it('validates project creation', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/projects', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('shows a project to its owner', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $project->id);
});

it('updates a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Old']);

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/projects/{$project->id}", ['name' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated');

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated']);
});

it('deletes a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/projects/{$project->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

it('forbids access to a project owned by another user', function () {
    $project = Project::factory()->create(); // принадлежит случайному юзеру
    $intruder = User::factory()->create();

    $this->actingAs($intruder, 'sanctum')
        ->getJson("/api/projects/{$project->id}")
        ->assertForbidden();

    $this->actingAs($intruder, 'sanctum')
        ->deleteJson("/api/projects/{$project->id}")
        ->assertForbidden();
});
