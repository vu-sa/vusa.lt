<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Uri;

class ContentEmbedUrl implements ValidationRule
{
    private const array HOSTS = [
        'spotify-embed' => [
            'spotify.com', 'open.spotify.com',
            'mixcloud.com', 'www.mixcloud.com', 'player-widget.mixcloud.com',
        ],
        'social-embed' => [
            'facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com', 'fb.watch', 'www.fb.watch',
            'instagram.com', 'www.instagram.com', 'm.instagram.com', 'instagr.am', 'www.instagr.am',
        ],
    ];

    public function __construct(private readonly string $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $url = is_string($value) ? Uri::of($value) : null;
            $valid = $url !== null
                && ! str_contains($value, '\\')
                && ! preg_match('/[\x00-\x1f\x7f]/', $value)
                && $url->scheme() === 'https'
                && in_array($url->host(), self::HOSTS[$this->type] ?? [], true)
                && ! $url->user() && ! $url->password()
                && ($url->port() === null || $url->port() === 443);
        } catch (\Throwable) {
            $valid = false;
        }

        if (! $valid) {
            $fail('validation.url')->translate();
        }
    }
}
