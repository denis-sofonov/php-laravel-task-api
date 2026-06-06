<?php

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Tests\TestCase;

// Eloquent models need the framework booted (no DB access here).
uses(TestCase::class);

it('allows the owner to view their project', function () {
    $owner = new User;
    $owner->id = 1;

    $project = new Project;
    $project->user_id = 1;

    expect((new ProjectPolicy)->view($owner, $project))->toBeTrue();
});

it('forbids a non-owner from viewing the project', function () {
    $owner = new User;
    $owner->id = 1;

    $project = new Project;
    $project->user_id = 2;

    expect((new ProjectPolicy)->view($owner, $project))->toBeFalse()
        ->and((new ProjectPolicy)->update($owner, $project))->toBeFalse()
        ->and((new ProjectPolicy)->delete($owner, $project))->toBeFalse();
});
