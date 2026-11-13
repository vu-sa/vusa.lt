<?php

namespace App\Services\Learning;

readonly class FetchResult
{
    public function __construct(
        public string $finalUrl,
        public int $status,
        public ?string $contentType = null,
        public ?string $version = null,
        public ?string $body = null,
        public ?string $reason = null,
    ) {}
}
