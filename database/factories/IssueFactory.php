<?php

namespace Database\Factories;

use App\Issue\Models\Category;
use App\Issue\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    protected $model = Issue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'category_id' => Category::query()->inRandomOrder()->value('id') ?? Category::factory(),
            'status' => 'open',
        ];
    }
}
