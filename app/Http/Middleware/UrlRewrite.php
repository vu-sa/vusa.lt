<?php

namespace App\Http\Middleware;

use Closure;

class UrlRewrite
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (substr(request()->path(), 0, 8) == 'uploads/' &&
        substr(request()->path(), 0, 13) != 'uploads/files') {
            $upload_path = '/uploads/files'.substr(request()->getPathInfo(), 8);

            return redirect($upload_path)->send();
        }

        // Visos nuorodos perrašomos į '/lt'

        // if (
        //     substr(request()->path(), 0, 3) != "lt/" &&
        //     request()->path() != 'lt' &&
        //     request()->path() != "en" &&
        //     substr(request()->path(), 0, 3) != "en/"
        // ) {
        //     return redirect('/lt' . request()->getPathInfo())->send();
        // }

        return $next($request);
    }
}
