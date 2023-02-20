<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (auth()->user()) {
            if (auth()->user()->role == 1) {
                return $next($request);
            } else {
                return response()->json(['status' => 'Access denied'], 403);
            }
        }

        return response()->json(['status' => 'Unauthorized'], 401);
    }
}
