<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

// class CustomRateLimitMiddleware
// {
//     // public function handle($request, Closure $next, $key, $maxRequests, $decaySeconds)
//     // {
//     //     $cacheKey = 'rate_limit:' . $key;

//     //     $requests = Cache::get($cacheKey, 0);
//     //     $requests++;

//     //     if ($requests > $maxRequests) {
//     //         return response()->json(['message' => 'Rate limit exceeded'], 429);
//     //     }

//     //     Cache::put($cacheKey, $requests, now()->addSeconds($decaySeconds));

//     //     return $next($request);
//     // }


//     public function handle($request, Closure $next, $key, $maxRequests, $decaySeconds)
//     {
//         // Unique key using route, id parameter, and key prefix
//         $uniqueKey = "{$key}:{$request->path()}";

//         // Apply rate limiting
//         if (RateLimiter::tooManyAttempts($uniqueKey, $maxRequests)) {
//             return response()->json([
//                 'message' => 'Too many requests. Please try again later.',
//                 "error" => 'Too many requests. Please try again later.',
//                 "responseMessage" => 'Too many requests. Please try again later.'
//             ],429);
//         }

//         // Increment attempt count
//         RateLimiter::hit($uniqueKey, $decaySeconds);

//         return $next($request);
//     }
// }


class CustomRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle($request, Closure $next, $key, $maxAttempts = 1, $decayMinutes = 1)
    {
        // dd($decayMinutes, $maxAttempts);
        $rateLimitKey = $request->id;
        $maxAttempts = (int) $maxAttempts;
        $decaySeconds = $decayMinutes;

        // Check current attempts
        $attempts = Cache::get($rateLimitKey, 0);

        if ($attempts >= $maxAttempts) {
            $retryAfter = Cache::get("{$rateLimitKey}:timer", time() + $decaySeconds) - time();

            // Return Too Many Requests response
            // return response()->json([
            //     'status' => 'error',
            //     'message' => 'Too many requests. Please try again later.',
            //     'retry_after' => $retryAfter,
            // ], 429);
            return [true, $retryAfter];
        }

        // Increment attempts and set timer if necessary
        Cache::put($rateLimitKey, $attempts + 1, $decaySeconds);
        if (!Cache::has("{$rateLimitKey}:timer")) {
            Cache::put("{$rateLimitKey}:timer", time() + $decaySeconds, $decaySeconds);
        }

        // return $next($request);
        return [false];
    }
}