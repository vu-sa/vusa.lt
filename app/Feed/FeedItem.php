<?php

namespace App\Feed;

use Spatie\Feed\FeedItem as SpatieFeedItem;

/**
 * Extends Spatie's FeedItem with a full-body {@see $content} field so the RSS
 * view can emit a {@see <content:encoded>} element alongside the shorter
 * {@see <description>}, plus {@see $alternates} for per-item language links.
 *
 * The parent's {@see __get}}/{@see __isset} already resolve the properties, so
 * views only need to guard with __isset('content').
 *
 * @property array<int, array{hreflang: string, href: string}> $alternates
 */
class FeedItem extends SpatieFeedItem
{
    protected ?string $content = null;

    /** @var array<int, array{hreflang: string, href: string}> */
    protected array $alternates = [];

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param  array<int, array{hreflang: string, href: string}>  $alternates
     */
    public function alternates(array $alternates): self
    {
        $this->alternates = $alternates;

        return $this;
    }
}
