<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Ownership-based: a user may act on a project only if they own it.
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }
}
