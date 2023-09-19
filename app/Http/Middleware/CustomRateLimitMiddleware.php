<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class CustomRateLimitMiddleware
{
    public function handle($request, Closure $next, $key, $maxRequests, $decaySeconds)
    {
        $cacheKey = 'rate_limit:' . $key;

        $requests = Cache::get($cacheKey, 0);
        $requests++;

        if ($requests > $maxRequests) {
            return response()->json(['message' => 'Rate limit exceeded'], 429);
        }

        Cache::put($cacheKey, $requests, now()->addSeconds($decaySeconds));

        return $next($request);
    }
}
