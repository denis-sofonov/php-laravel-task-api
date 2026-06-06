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
 * Manage the authenticated user's projects.
 *
 * @authenticated
 */
class ProjectController extends Controller
{
    /**
     * List the authenticated user's projects (paginated).
     *
     * @queryParam search string Filter by name. Example: redesign
     * @queryParam sort string created_at or name, prefix with - for descending. Example: -created_at
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
            ->withCount('tasks'); // tasks_count without loading the tasks (avoids N+1)

        if (isset($filters['search'])) {
            $query->where('name', 'ilike', '%'.$filters['search'].'%');
        }

        // Whitelist guards against arbitrary columns being injected into ORDER BY.
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
     * Create a project for the authenticated user.
     *
     * @apiResource App\Http\Resources\ProjectResource
     *
     * @apiResourceModel App\Models\Project
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        // Saving through the relation sets user_id automatically.
        $project = $request->user()->projects()->create($request->validated());

        dispatch(LogProjectActivity::created($project));

        return ProjectResource::make($project)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single project.
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
     * Update a project.
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
     * Delete a project (its tasks are removed via the migration's cascade).
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
