<?php

namespace App\Issue\Http\Controllers;

use App\Issue\Http\Requests\StoreIssueRequest;
use App\Issue\Http\Requests\UpdateIssueRequest;
use App\Issue\Http\Resources\IssueResource;
use App\Issue\Models\Category;
use App\Issue\Models\Issue;
use App\Issue\Services\Summary\SummaryServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class IssueController
{
    public function index(
        Request $request,
    ): AnonymousResourceCollection|JsonResponse {
        try {
            $issues = Issue::query()
                ->with("category")
                ->when(
                    $request->string("status")->toString(),
                    fn($q, $v) => $q->where("status", $v),
                )
                ->when(
                    $request->string("priority")->toString(),
                    fn($q, $v) => $q->where("priority", $v),
                )
                ->when($request->string("category")->toString(), function (
                    $q,
                    $slug,
                ) {
                    $category = Category::findBySlug($slug);
                    $q->where("category_id", $category?->id ?? 0);
                })
                ->when($request->has("escalated"), function ($q) use (
                    $request,
                ) {
                    $isEscalated = filter_var(
                        $request->input("escalated"),
                        FILTER_VALIDATE_BOOLEAN,
                    );
                    $isEscalated
                        ? $q->whereNotNull("escalated_at")
                        : $q->whereNull("escalated_at");
                })
                ->latest()
                ->paginate(15);

            return IssueResource::collection($issues);
        } catch (Throwable $e) {
            return $this->handleError("issues.index.failed", $e, $request);
        }
    }

    public function store(
        StoreIssueRequest $request,
        SummaryServiceInterface $summaryService,
    ): IssueResource|JsonResponse {
        try {
            $validated = $request->validated();
            $category = Category::findBySlug($validated["category"]);

            $summaryResult = $summaryService->generate($validated["description"]);

            $issue = DB::transaction(
                fn() => Issue::query()->create([
                    ...$validated,
                    "category_id" => $category->id,
                    "summary" => $summaryResult["summary"],
                    "suggested_action" => $summaryResult["suggested_action"],
                ]),
            );

            Log::channel("api")->info("issues.store.summary_generated", [
                "issue_id" => $issue->id,
                "source" => $summaryResult["source"],
                "matched_playbook_slug" => $summaryResult["matched_playbook_slug"],
                "confidence" => $summaryResult["confidence"],
            ]);

            return new IssueResource($issue->load("category"));
        } catch (Throwable $e) {
            return $this->handleError("issues.store.failed", $e, $request);
        }
    }

    public function show(Issue $issue): IssueResource|JsonResponse
    {
        try {
            return new IssueResource($issue->load("category"));
        } catch (Throwable $e) {
            return $this->handleError("issues.show.failed", $e, request(), [
                "issue_id" => $issue->id,
            ]);
        }
    }

    public function update(
        UpdateIssueRequest $request,
        Issue $issue,
    ): IssueResource|JsonResponse {
        try {
            $validated = $request->validated();

            if (isset($validated["if_unmodified_since"])) {
                $clientTime = CarbonImmutable::parse(
                    $validated["if_unmodified_since"],
                )->getTimestamp();
                $serverTime = $issue->updated_at->getTimestamp();

                if ($clientTime !== $serverTime) {
                    return response()->json(
                        [
                            "message" =>
                                "The resource has been modified since you last retrieved it.",
                            "current_updated_at" => $issue->updated_at->toIso8601String(),
                        ],
                        409,
                    );
                }

                unset($validated["if_unmodified_since"]);
            }

            if (isset($validated["category"])) {
                $category = Category::findBySlug($validated["category"]);
                $validated["category_id"] = $category->id;
                unset($validated["category"]);
            }

            DB::transaction(fn() => $issue->update($validated));

            return new IssueResource($issue->fresh()->load("category"));
        } catch (Throwable $e) {
            return $this->handleError("issues.update.failed", $e, $request, [
                "issue_id" => $issue->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function handleError(
        string $event,
        Throwable $e,
        Request $request,
        array $extra = [],
    ): JsonResponse {
        Log::channel("api")->error($event, [
            "exception" => $e::class,
            "message" => $e->getMessage(),
            "file" => $e->getFile() . ":" . $e->getLine(),
            "method" => $request->method(),
            "path" => $request->path(),
            "payload" => $request->all(),
            ...$extra,
        ]);

        return response()->json(
            [
                "message" => "An error occurred while processing your request.",
            ],
            500,
        );
    }
}
