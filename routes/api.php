<?php

use App\Issue\Http\Controllers\CategoryController;
use App\Issue\Http\Controllers\IssueController;
use App\Issue\Http\Controllers\PlaybookController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::apiResource('issues', IssueController::class)
        ->only(['index', 'store', 'show', 'update']);

    Route::apiResource('playbook', PlaybookController::class)
        ->only(['index', 'show']);

    Route::get('categories', [CategoryController::class, 'index'])
        ->name('categories.index');
});
