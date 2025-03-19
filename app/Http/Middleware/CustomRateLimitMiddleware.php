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
        $rateLimitKey = $request->id; // Gunakan ID dari request sebagai key
        $maxAttempts = (int) $maxAttempts;
        $decaySeconds = $decayMinutes * 60; // Konversi menit ke detik

        // Ambil jumlah percobaan (attempts) saat ini dari cache
        $attempts = Cache::get($rateLimitKey, 0);

        if ($attempts >= $maxAttempts) {
            $retryAfter = Cache::get("{$rateLimitKey}:timer", time() + $decaySeconds) - time();

            // Jika sudah melebihi rate limit, kirim response JSON
            return response()->json([
                'status' => 'error',
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi setelah ' . $retryAfter . ' detik.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        // Jika belum melebihi, increment attempt count
        Cache::put($rateLimitKey, $attempts + 1, $decaySeconds);

        // Set timer jika belum ada
        if (!Cache::has("{$rateLimitKey}:timer")) {
            Cache::put("{$rateLimitKey}:timer", time() + $decaySeconds, $decaySeconds);
        }

        // Lanjutkan request
        return $next($request);
    }
}