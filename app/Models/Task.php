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
     * Default attributes for a new model, so status is always set even when the
     * client omits it.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TaskStatus::Todo->value,
    ];

    /**
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
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
