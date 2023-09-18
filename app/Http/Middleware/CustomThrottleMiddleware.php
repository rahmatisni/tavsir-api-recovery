<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class CustomThrottleMiddleware extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        // Customize the request signature as needed for per-second rate limiting
        return sha1($request->method() . '|' . $request->id());
    }
}


// class CustomThrottleMiddleware
// {
//     /**
//      * Handle an incoming request.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
//      * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
//      */
//     public function handle(Request $request, Closure $next)
//     {
//         return $next($request);
//     }
// }
