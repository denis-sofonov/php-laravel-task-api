<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Пример фоновой задачи: выполняется воркером очереди, а не в HTTP-запросе.
 * Здесь просто пишем структурный лог, но точно так же выносят в очередь
 * отправку писем, генерацию отчётов, обращения к внешним API и т.п.
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
     * Удобный конструктор для события "проект создан".
     */
    public static function created(Project $project): self
    {
        return new self($project->id, 'created');
    }
}
