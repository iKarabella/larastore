<?php

namespace App\Modules\Larastore\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketMiddleware
{    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::guest() || !array_key_exists(Auth::user()->id, config('app.market_rights'))) abort(403);

        return $next($request);
    }
}
