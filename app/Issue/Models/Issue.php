<?php

namespace App\Issue\Models;

use App\Issue\Enums\Priority;
use App\Issue\Enums\Status;
use App\Issue\Services\EscalationService;
use Carbon\CarbonImmutable;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property Priority $priority
 * @property int $category_id
 * @property Status $status
 * @property string|null $summary
 * @property string|null $suggested_action
 * @property CarbonImmutable|null $due_date
 * @property CarbonImmutable|null $escalated_at
 * @property string|null $issuer
 * @property string|null $issuer_email
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Category|null $category
 */
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        "title",
        "description",
        "priority",
        "category_id",
        "status",
        "summary",
        "suggested_action",
        "due_date",
        "issuer",
        "issuer_email",
    ];

    /** @var array<string, string> */
    protected $attributes = [
        "status" => "open",
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            "priority" => Priority::class,
            "status" => Status::class,
            "due_date" => "datetime",
            "escalated_at" => "datetime",
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function newFactory(): Factory
    {
        return IssueFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Issue $issue): void {
            if (empty($issue->priority) && $issue->category_id) {
                $category = Category::query()->find($issue->category_id);
                if ($category) {
                    $issue->priority = $category->severity_level;
                }
            }

            $escalation = new EscalationService();
            $escalation->evaluate($issue);
        });

        static::updating(function (Issue $issue): void {
            if ($issue->isDirty("category_id")) {
                $category = Category::query()->find($issue->category_id);
                if ($category) {
                    $issue->priority = $category->severity_level;
                }
            }

            $escalation = new EscalationService();
            $escalation->evaluate($issue);
        });
    }
}
