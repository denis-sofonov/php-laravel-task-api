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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Creates a new user if an owner isn't provided explicitly.
            'user_id' => User::factory(),
            'name' => fake()->unique()->catchPhrase(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
