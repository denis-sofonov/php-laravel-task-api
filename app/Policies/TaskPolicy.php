<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * A task belongs to a user through its project; ownership is checked there.
 */
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $task->project->user_id === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->project->user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->project->user_id === $user->id;
    }
}
