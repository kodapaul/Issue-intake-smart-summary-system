<?php

namespace App\Issue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Issue\Models\Issue
 */
class IssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "description" => $this->description,
            "priority" => $this->priority?->value,
            "category" => $this->whenLoaded('category', fn () => [
                "slug" => $this->category->slug,
                "name" => $this->category->name,
                "severity_level" => $this->category->severity_level?->value,
            ]),
            "status" => $this->status?->value,
            "summary" => $this->summary,
            "suggested_action" => $this->suggested_action,
            "due_date" => $this->due_date?->toIso8601String(),
            "escalated_at" => $this->escalated_at?->toIso8601String(),
            "is_escalated" => $this->escalated_at !== null,
            "issuer" => $this->issuer,
            "issuer_email" => $this->issuer_email,
            "created_at" => $this->created_at?->toIso8601String(),
            "updated_at" => $this->updated_at?->toIso8601String(),
        ];
    }
}
