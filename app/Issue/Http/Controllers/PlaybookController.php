<?php

namespace App\Issue\Http\Controllers;

use App\Issue\Http\Resources\PlaybookEntryResource;
use App\Issue\Models\PlaybookEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlaybookController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $entries = PlaybookEntry::query()
                ->orderBy('name')
                ->get();

            return PlaybookEntryResource::collection($entries);
        } catch (Throwable $e) {
            return $this->handleError('playbook.index.failed', $e, $request);
        }
    }

    public function show(PlaybookEntry $playbook): PlaybookEntryResource|JsonResponse
    {
        try {
            return new PlaybookEntryResource($playbook);
        } catch (Throwable $e) {
            return $this->handleError('playbook.show.failed', $e, request(), [
                'playbook_slug' => $playbook->slug,
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
        Log::channel('api')->error($event, [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'method' => $request->method(),
            'path' => $request->path(),
            ...$extra,
        ]);

        return response()->json(
            ['message' => 'An error occurred while processing your request.'],
            500,
        );
    }
}
