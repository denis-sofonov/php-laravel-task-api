<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Example background job: runs on a queue worker, not in the HTTP request.
 * It only writes a structured log here, but the same pattern offloads emails,
 * report generation, external API calls, etc.
 */
class LogProjectActivity implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $projectId,
        private readonly string $action,
    ) {}

    public function handle(): void
    {
        Log::channel('structured')->info('project.activity', [
            'project_id' => $this->projectId,
            'action' => $this->action,
        ]);
    }

    /**
     * Convenience constructor for the "project created" event.
     */
    public static function created(Project $project): self
    {
        return new self($project->id, 'created');
    }
}
