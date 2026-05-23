<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Принадлежит ли задача пользователю (через её проект).
     */
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
