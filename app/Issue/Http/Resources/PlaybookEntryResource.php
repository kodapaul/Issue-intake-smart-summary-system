<?php

namespace App\Issue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Issue\Models\PlaybookEntry
 */
class PlaybookEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'summary_template' => $this->summary_template,
            'suggested_action' => $this->suggested_action,
            'troubleshooting_steps' => $this->troubleshooting_steps,
            'faqs' => $this->faqs,
            'category_hint' => $this->category_hint,
            'priority_hint' => $this->priority_hint,
            'triggers' => $this->when(
                $request->boolean('include_triggers'),
                fn () => $this->triggers,
            ),
        ];
    }
}
