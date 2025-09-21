<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class licencia
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

            $config = config::find(1);

            $hoy = date('Y-m-d');

           if( Storage::disk('config')->exists('licencia.ptdo') || $hoy < $config->path_config) {

            return $next($request);

           } else {

            return redirect()->route('licencia');

           }
        
    }
}
