<?php

namespace App\Issue\Services;

use App\Issue\Enums\Priority;
use App\Issue\Enums\Status;
use App\Issue\Models\Issue;
use Illuminate\Support\Facades\Date;

class EscalationService
{
    public function shouldEscalate(Issue $issue): bool
    {
        if ($issue->priority !== Priority::High) {
            return false;
        }

        if (
            in_array(
                $issue->status,
                [Status::Resolved, Status::Closed],
                strict: true,
            )
        ) {
            return false;
        }

        if ($issue->due_date === null) {
            return false;
        }

        return $issue->due_date->isPast();
    }

    public function evaluate(Issue $issue): void
    {
        if ($issue->escalated_at !== null) {
            return;
        }

        if ($this->shouldEscalate($issue)) {
            $issue->escalated_at = Date::now();
        }
    }
}
