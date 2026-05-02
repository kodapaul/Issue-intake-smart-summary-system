<?php

namespace Database\Seeders;

use App\Issue\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'incident',
                'name' => 'Incident',
                'severity_level' => 'high',
                'description' => 'Active outage or service disruption requiring immediate attention.',
            ],
            [
                'slug' => 'bug',
                'name' => 'Bug Report',
                'severity_level' => 'medium',
                'description' => 'Defect in existing functionality with available workaround.',
            ],
            [
                'slug' => 'support',
                'name' => 'Support',
                'severity_level' => 'medium',
                'description' => 'User assistance request or how-to question.',
            ],
            [
                'slug' => 'feature_request',
                'name' => 'Feature Request',
                'severity_level' => 'low',
                'description' => 'Proposed enhancement or new capability.',
            ],
            [
                'slug' => 'other',
                'name' => 'Other',
                'severity_level' => 'low',
                'description' => 'Uncategorized — pending triage.',
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
