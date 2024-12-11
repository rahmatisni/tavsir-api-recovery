<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class CustomRateLimitMiddleware
{
    // public function handle($request, Closure $next, $key, $maxRequests, $decaySeconds)
    // {
    //     $cacheKey = 'rate_limit:' . $key;

    //     $requests = Cache::get($cacheKey, 0);
    //     $requests++;

    //     if ($requests > $maxRequests) {
    //         return response()->json(['message' => 'Rate limit exceeded'], 429);
    //     }

    //     Cache::put($cacheKey, $requests, now()->addSeconds($decaySeconds));

    //     return $next($request);
    // }


    public function handle($request, Closure $next, $key, $maxRequests, $decaySeconds)
    {
        // Unique key using route, id parameter, and key prefix
        $uniqueKey = "{$key}:{$request->path()}";

        // Apply rate limiting
        if (RateLimiter::tooManyAttempts($uniqueKey, $maxRequests)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                "error" => 'Too many requests. Please try again later.',
                "responseMessage" => 'Too many requests. Please try again later.'
            ],429);
        }

        // Increment attempt count
        RateLimiter::hit($uniqueKey, $decaySeconds);

        return $next($request);
    }
}
