<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }

    /**
     * "Done" state — an example named state for tests.
     */
    public function done(): static
    {
        return $this->state(['status' => TaskStatus::Done]);
    }
}
