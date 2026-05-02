<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        Log::channel("api")->info("api.request", [
            "method" => $request->method(),
            "path" => $request->path(),
            "query" => $request->query(),
            "ip" => $request->ip(),
        ]);

        $response = $next($request);

        $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
        $status = $response->getStatusCode();

        Log::channel("api")->log(
            $status >= 500 ? "error" : ($status >= 400 ? "warning" : "info"),
            "api.response",
            [
                "method" => $request->method(),
                "path" => $request->path(),
                "status" => $status,
                "duration_ms" => $durationMs,
            ],
        );

        return $response;
    }
}
