<?php

namespace App\Issue\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property array<int, string> $triggers
 * @property string $summary_template
 * @property string $suggested_action
 * @property array<int, string> $troubleshooting_steps
 * @property array<int, array{q: string, a: string}> $faqs
 * @property string|null $category_hint
 * @property string|null $priority_hint
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class PlaybookEntry extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'triggers',
        'summary_template',
        'suggested_action',
        'troubleshooting_steps',
        'faqs',
        'category_hint',
        'priority_hint',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'triggers' => 'array',
            'troubleshooting_steps' => 'array',
            'faqs' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }
}
