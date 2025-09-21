<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class superuser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->is_superuser == 0) {
            return redirect()->route('home');
        } else{
            return $next($request);
        }
    }
}
