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

/**
 * @group Projects
 *
 * Управление проектами текущего пользователя.
 *
 * @authenticated
 */
class ProjectController extends Controller
{
    /**
     * Список проектов текущего пользователя (с пагинацией).
     *
     * @queryParam search string Поиск по названию. Example: redesign
     * @queryParam sort string Сортировка: created_at или name, с префиксом - для убывания. Example: -created_at
     *
     * @apiResourceCollection App\Http\Resources\ProjectResource
     *
     * @apiResourceModel App\Models\Project paginate=15
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
     *
     * @apiResource App\Http\Resources\ProjectResource
     *
     * @apiResourceModel App\Models\Project
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
     *
     * @apiResource App\Http\Resources\ProjectResource
     *
     * @apiResourceModel App\Models\Project
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return ProjectResource::make($project->loadCount('tasks'));
    }

    /**
     * Обновить проект.
     *
     * @apiResource App\Http\Resources\ProjectResource
     *
     * @apiResourceModel App\Models\Project
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return ProjectResource::make($project);
    }

    /**
     * Удалить проект (вместе с задачами — каскадом из миграции).
     *
     * @response 204 scenario="Deleted"
     */
    public function destroy(Project $project): Response
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->noContent();
    }
}
