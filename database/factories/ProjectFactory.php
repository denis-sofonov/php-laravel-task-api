<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Состояние по умолчанию для одного проекта.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Если владельца не передали явно — создастся новый пользователь.
            'user_id' => User::factory(),
            'name' => fake()->unique()->catchPhrase(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
