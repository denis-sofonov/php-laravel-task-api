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
     * Seed the database with demo data.
     */
    public function run(): void
    {
        // Known user for manual testing (log in with these credentials).
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 3 projects for this user, each with 5 tasks.
        Project::factory(3)
            ->for($user)
            ->hasTasks(5)
            ->create();
    }
}
