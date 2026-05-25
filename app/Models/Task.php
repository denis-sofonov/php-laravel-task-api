<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property Carbon|null $due_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['title', 'description', 'status', 'due_date'];

    /**
     * Значения по умолчанию для новой модели (до сохранения в БД).
     * Благодаря этому status всегда задан, даже если клиент его не прислал.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TaskStatus::Todo->value,
    ];

    /**
     * Преобразование типов при чтении/записи.
     * status автоматически становится объектом enum TaskStatus.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date',
        ];
    }

    /**
     * Задача принадлежит одному проекту.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
