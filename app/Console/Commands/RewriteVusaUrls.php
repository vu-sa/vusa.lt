<?php

namespace App\Console\Commands;

use App\Models\Navigation;
use App\Models\QuickLink;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Uri;

#[Description('Rewrite www.vusa.lt navigation and quick-link URLs to APP_URL')]
#[Signature('urls:rewrite-vusa')]
class RewriteVusaUrls extends Command
{
    private const string SOURCE_HOST = 'www.vusa.lt';

    public function handle(): int
    {
        $targetUrl = (string) config('app.url');

        try {
            $target = Uri::of($targetUrl);
        } catch (\Throwable) {
            $this->error("Refused: APP_URL '{$targetUrl}' is not a valid URL.");

            return self::FAILURE;
        }

        if ($target->host() === null) {
            $this->error("Refused: APP_URL '{$targetUrl}' must include a host.");

            return self::FAILURE;
        }

        $navigationUrls = $this->rewrite(Navigation::withTrashed(), 'url', $target);
        $quickLinkUrls = $this->rewrite(QuickLink::withTrashed(), 'link', $target);

        $this->info("Rewrote {$navigationUrls} navigation URL(s) and {$quickLinkUrls} quick link(s) to {$targetUrl}.");

        return self::SUCCESS;
    }

    /**
     * @param  Builder<Navigation>|Builder<QuickLink>  $query
     */
    private function rewrite(Builder $query, string $attribute, Uri $target): int
    {
        $rewritten = 0;

        $query->lazyById(200)->each(function (Model $model) use ($attribute, $target, &$rewritten): void {
            $url = $model->getAttribute($attribute);

            if (! is_string($url)) {
                return;
            }

            try {
                $uri = Uri::of($url);
            } catch (\Throwable) {
                return;
            }

            if (strtolower((string) $uri->host()) !== self::SOURCE_HOST) {
                return;
            }

            $model->setAttribute(
                $attribute,
                (string) $uri
                    ->withScheme((string) $target->scheme())
                    ->withHost((string) $target->host())
                    ->withPort($target->port()),
            );
            $model->save();
            $rewritten++;
        });

        return $rewritten;
    }
}
