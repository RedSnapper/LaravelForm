<?php

namespace RS\Form\Middleware;

use Closure;

class HoneyPot
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!is_null($request->get('formlet-email')) || $request->get('formlet-terms')){
            return redirect()->back();
        }

        return $next($request);
    }
}