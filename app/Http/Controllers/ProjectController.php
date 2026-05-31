<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Jobs\LogProjectActivity;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    /**
     * Список проектов текущего пользователя (с пагинацией).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'max:50'],
        ]);

        $query = $request->user()
            ->projects()
            ->withCount('tasks'); // tasks_count без загрузки всех задач (нет N+1)

        if (isset($filters['search'])) {
            $query->where('name', 'ilike', '%'.$filters['search'].'%');
        }

        $sort = $filters['sort'] ?? '-created_at';
        $column = ltrim($sort, '-');
        if (in_array($column, ['created_at', 'name'], true)) {
            $query->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $projects = $query->paginate(15)->withQueryString();

        return ProjectResource::collection($projects);
    }

    /**
     * Создать проект для текущего пользователя.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        // create() через связь сам проставит user_id.
        $project = $request->user()->projects()->create($request->validated());

        // Фоновая задача: уходит в очередь, не задерживает HTTP-ответ.
        dispatch(LogProjectActivity::created($project));

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Показать один проект.
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return ProjectResource::make($project->loadCount('tasks'));
    }

    /**
     * Обновить проект.
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return ProjectResource::make($project);
    }

    /**
     * Удалить проект (вместе с задачами — каскадом из миграции).
     */
    public function destroy(Project $project): Response
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
