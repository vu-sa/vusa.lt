<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicUrl;
use Illuminate\Database\Eloquent\Model;

class PublicUrlService
{
    /**
     * Records $url as a retired URL for $model/$locale — the only way a row ever enters
     * public_urls. Called from a model's `saved` hook exactly when its permalink (or whatever
     * else the URL is derived from) actually changed, never speculatively; the *current* URL is
     * never stored anywhere, it's always computed live via the model's own `publicUrl()`.
     */
    public function recordLegacyUrl(Model $model, string $locale, string $url): void
    {
        // Re-adopting a permalink that was itself retired earlier, or saving twice in the same
        // request, must not violate the `url` unique index — a matching row already means this
        // exact URL is already on record as retired.
        if (PublicUrl::query()->where('url', $url)->exists()) {
            return;
        }

        PublicUrl::query()->create([
            'urlable_type' => $model->getMorphClass(),
            'urlable_id' => $model->getKey(),
            'locale' => $locale,
            'url' => $url,
        ]);
    }

    public function resolve(string $url): ?PublicUrl
    {
        return PublicUrl::query()->with('urlable')->where('url', $url)->first();
    }

    /**
     * Whether $url is already on record as a retired permalink belonging to a *different* record
     * than ($exceptType, $exceptId) — reusing it would either silently steal that record's old
     * redirect right away, or break unrecoverably the next time this new owner itself moves on
     * (recordLegacyUrl()'s own dedup guard would then find the url "already taken" by the other
     * record and skip writing anything). Passing both null checks against every existing owner —
     * the correct check when assigning a permalink to a brand-new record.
     */
    public function isRetiredByAnother(string $url, ?string $exceptType = null, ?int $exceptId = null): bool
    {
        $query = PublicUrl::query()->where('url', $url);

        if ($exceptType !== null && $exceptId !== null) {
            $query->where(function ($q) use ($exceptType, $exceptId): void {
                $q->where('urlable_type', '!=', $exceptType)->orWhere('urlable_id', '!=', $exceptId);
            });
        }

        return $query->exists();
    }

    public function destinationFor(PublicUrl $publicUrl): ?string
    {
        $target = $publicUrl->urlable;

        if (! $target instanceof Model || ! $this->isPubliclyReachable($target)) {
            return null;
        }

        return match (true) {
            $target instanceof Calendar => $target->publicUrl($publicUrl->locale),
            $target instanceof News, $target instanceof Page => $target->publicUrl(),
            default => null,
        };
    }

    private function isPubliclyReachable(Model $model): bool
    {
        return match (true) {
            $model instanceof Calendar => ! $model->is_draft,
            $model instanceof News => ! $model->draft && ($model->publish_time === null || $model->publish_time->isPast()),
            $model instanceof Page => $model->is_active,
            default => false,
        };
    }
}
