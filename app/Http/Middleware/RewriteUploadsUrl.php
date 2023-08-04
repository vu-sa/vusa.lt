<?php

namespace App\Http\Middleware;

use Closure;

class RewriteUploadsUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * Historically, many links in the database were stored without the
         * .../files/... part. This middleware redirects those links to the
         * correct location.
         */
        if (substr(request()->path(), 0, 8) == 'uploads/' &&
        substr(request()->path(), 0, 13) != 'uploads/files') {
            $upload_path = '/uploads/files'.substr(request()->getPathInfo(), 8);

            return redirect($upload_path)->send();
        }

        return $next($request);
    }
}
