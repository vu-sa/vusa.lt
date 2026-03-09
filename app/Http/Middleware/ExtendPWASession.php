<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class ExtendPWASession
{
    /**
     * Extended session lifetime for PWA mode (in minutes).
     * 2 weeks = 20160 minutes
     */
    private const PWA_SESSION_LIFETIME = 20160;

    /**
     * Handle an incoming request.
     *
     * If the request comes from an installed PWA (detected via cookie),
     * extend the session lifetime and disable expire_on_close.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isPWARequest($request)) {
            // Extend session lifetime for PWA users
            Config::set('session.lifetime', self::PWA_SESSION_LIFETIME);

            // Don't expire session when browser closes (PWA stays "open")
            Config::set('session.expire_on_close', false);
        }

        return $next($request);
    }

    /**
     * Check if the request is coming from an installed PWA.
     */
    private function isPWARequest(Request $request): bool
    {
        // Check for PWA cookie set by the client
        return $request->cookie('pwa_mode') === '1';
    }
}
