<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class CustomThrottleMiddleware extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        return $request->route('id');
    }
}
