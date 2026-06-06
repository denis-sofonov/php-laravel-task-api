<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * @group Tasks
 *
 * Tasks that belong to a project.
 *
 * @authenticated
 */
class TaskController extends Controller
{
    /**
     * List the tasks of a project (paginated).
     *
     * @queryParam status string Filter by status: todo, in_progress, done. Example: todo
     * @queryParam search string Filter by title. Example: deploy
     * @queryParam sort string created_at, due_date, title, status (prefix with - for descending). Example: -due_date
     *
     * @apiResourceCollection App\Http\Resources\TaskResource
     *
     * @apiResourceModel App\Models\Task paginate=15
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $filters = $request->validate([
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'max:50'],
        ]);

        $query = $project->tasks();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // ilike is case-insensitive in PostgreSQL.
        if (isset($filters['search'])) {
            $query->where('title', 'ilike', '%'.$filters['search'].'%');
        }

        // Whitelist guards against arbitrary columns being injected into ORDER BY.
        $sort = $filters['sort'] ?? '-created_at';
        $column = ltrim($sort, '-');
        if (in_array($column, ['created_at', 'due_date', 'title', 'status'], true)) {
            $query->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $tasks = $query->paginate(15)->withQueryString();

        return TaskResource::collection($tasks);
    }

    /**
     * Create a task inside a project.
     *
     * @apiResource App\Http\Resources\TaskResource
     *
     * @apiResourceModel App\Models\Task
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        // Only the project owner may add tasks to it.
        $this->authorize('update', $project);

        $task = $project->tasks()->create($request->validated());

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single task.
     *
     * @apiResource App\Http\Resources\TaskResource
     *
     * @apiResourceModel App\Models\Task
     */
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return TaskResource::make($task);
    }

    /**
     * Update a task.
     *
     * @apiResource App\Http\Resources\TaskResource
     *
     * @apiResourceModel App\Models\Task
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return TaskResource::make($task);
    }

    /**
     * Delete a task.
     *
     * @response 204 scenario="Deleted"
     */
    public function destroy(Task $task): Response
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
