<?php

namespace App\Issue\Http\Controllers;

use App\Issue\Http\Resources\CategoryResource;
use App\Issue\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryController
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            return CategoryResource::collection(
                Category::query()->orderBy('name')->get(),
            );
        } catch (Throwable $e) {
            Log::channel('api')->error('categories.index.failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json(
                ['message' => 'An error occurred while processing your request.'],
                500,
            );
        }
    }
}
