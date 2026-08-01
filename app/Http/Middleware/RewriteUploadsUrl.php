<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RewriteUploadsUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * Historically, many links in the database were stored without the
         * .../files/... part. This middleware redirects those links to the
         * correct location.
         */
        if (str_starts_with(request()->path(), 'uploads/') &&
        ! str_starts_with(request()->path(), 'uploads/files')) {
            $upload_path = '/uploads/files'.substr(request()->getPathInfo(), 8);

            return redirect($upload_path)->send();
        }

        return $next($request);
    }
}
