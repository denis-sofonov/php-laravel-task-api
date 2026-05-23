<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Наполнение базы демонстрационными данными.
     */
    public function run(): void
    {
        // Известный пользователь для ручного тестирования (логин по этим данным).
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 3 проекта этого пользователя, в каждом по 5 задач.
        Project::factory(3)
            ->for($user)
            ->hasTasks(5)
            ->create();
    }
}
