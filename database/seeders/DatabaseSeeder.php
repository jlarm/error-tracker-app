<?php

namespace Database\Seeders;

use App\Models\ErrorLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Project::factory()
            ->count(3)
            ->sequence(
                ['name' => 'Marketing Site'],
                ['name' => 'Customer Portal'],
                ['name' => 'Internal API'],
            )
            ->create()
            ->each(function (Project $project) {
                ErrorLog::factory()
                    ->count(fake()->numberBetween(5, 12))
                    ->for($project)
                    ->create([
                        'last_seen_at' => fn () => fake()->dateTimeBetween('-7 days', 'now'),
                    ]);
            });
    }
}
