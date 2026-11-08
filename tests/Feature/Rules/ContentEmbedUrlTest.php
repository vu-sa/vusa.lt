<?php

use App\Rules\ContentEmbedUrl;
use Illuminate\Support\Facades\Validator;

test('embed fields accept supported provider addresses', function (string $type, string $url): void {
    $validator = Validator::make(['url' => $url], ['url' => ['url:https', new ContentEmbedUrl($type)]]);

    expect($validator->passes())->toBeTrue();
})->with([
    ['spotify-embed', 'https://open.spotify.com/embed/show/abc?utm_source=generator'],
    ['spotify-embed', 'https://spotify.com/show/abc'],
    ['spotify-embed', 'https://www.mixcloud.com/startfm/tiesiogiai-is-vu-sa/'],
    ['spotify-embed', 'https://player-widget.mixcloud.com/widget/iframe/?feed=%2Fstartfm%2F'],
    ['social-embed', 'https://www.facebook.com/vustudentusatstovybe/posts/123456'],
    ['social-embed', 'https://m.facebook.com/permalink.php?story_fbid=123&id=456'],
    ['social-embed', 'https://web.facebook.com/watch/?v=123456'],
    ['social-embed', 'https://fb.watch/xyz123/'],
    ['social-embed', 'https://www.instagram.com/p/C_abc123/?igsh=abc#details'],
    ['social-embed', 'https://instagr.am/reel/C_abc123/'],
]);

test('embed fields reject unsupported provider addresses', function (string $type, string $url): void {
    $validator = Validator::make(['url' => $url], ['url' => ['url:https', new ContentEmbedUrl($type)]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('url'))->toBeTrue();
})->with([
    ['spotify-embed', 'http://open.spotify.com/show/abc'],
    ['spotify-embed', 'https://example.org/show/abc'],
    ['spotify-embed', 'https://open.spotify.com.example.org/show/abc'],
    ['spotify-embed', 'https://user@open.spotify.com/show/abc'],
    ['spotify-embed', 'https://open.spotify.com:8443/show/abc'],
    ['spotify-embed', 'https://www.instagram.com/p/abc/'],
    ['spotify-embed', '//open.spotify.com/show/abc'],
    ['spotify-embed', 'data:text/html,example'],
    ['social-embed', 'http://www.facebook.com/posts/123'],
    ['social-embed', 'https://other.facebook.com/posts/123'],
    ['social-embed', 'https://www.instagram.com.example.org/p/abc/'],
    ['social-embed', 'https://user@www.instagram.com/p/abc/'],
    ['social-embed', 'https://www.facebook.com:8443/posts/123'],
    ['social-embed', 'https://open.spotify.com/show/abc'],
]);
