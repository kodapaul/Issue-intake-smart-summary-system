<?php

namespace Database\Seeders;

use App\User\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            CategorySeeder::class,
            PlaybookSeeder::class,
            IssueSeeder::class,
        ]);
    }
}
