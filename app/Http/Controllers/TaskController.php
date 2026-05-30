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

class TaskController extends Controller
{
    /**
     * Список задач конкретного проекта.
     * $project приходит из вложенного маршрута projects/{project}/tasks.
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

        // Фильтр по статусу.
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Поиск по названию (ilike — регистронезависимо в PostgreSQL).
        if (isset($filters['search'])) {
            $query->where('title', 'ilike', '%'.$filters['search'].'%');
        }

        // Сортировка по белому списку полей: ?sort=due_date или ?sort=-created_at.
        // Белый список защищает от инъекции произвольных колонок в ORDER BY.
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
     * Создать задачу внутри проекта.
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        // Изменять содержимое проекта может только его владелец.
        $this->authorize('update', $project);

        $task = $project->tasks()->create($request->validated());

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Показать одну задачу (shallow-маршрут tasks/{task}).
     */
    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return TaskResource::make($task);
    }

    /**
     * Обновить задачу.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return TaskResource::make($task);
    }

    /**
     * Удалить задачу.
     */
    public function destroy(Task $task): Response
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
