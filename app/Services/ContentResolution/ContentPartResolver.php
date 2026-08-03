<?php

namespace App\Services\ContentResolution;

use App\Models\ContentPart;
use App\Services\ContentResolution\Resolvers\CalendarBlockResolver;
use App\Services\ContentResolution\Resolvers\EventListResolver;
use App\Services\ContentResolution\Resolvers\LinkListResolver;
use App\Services\ContentResolution\Resolvers\NewsBlockResolver;
use Illuminate\Support\Collection;

/**
 * Orchestrates server-side resolution of "dynamic" rich-content blocks — the ones
 * whose display needs data the author didn't type (link-list, event-list), plus a
 * bridge for the legacy news/calendar types so they stop client-fetching on ordinary
 * pages. `person-quote` deliberately has no resolver: it stores an author-approved
 * snapshot, so resolving it would add a per-render User query and a PII surface for
 * freshness nobody asked for (see the frontend registry's `serverResolved` flag).
 *
 * Explicit map, not container-tagged bindings — greppable, no service provider wiring,
 * and `resolvableTypes()` is the single PHP-side source of truth the frontend registry
 * test is asserted against.
 */
final class ContentPartResolver
{
    /** @var array<string, class-string<ResolvesContentPart>> */
    private const array RESOLVERS = [
        'link-list' => LinkListResolver::class,
        'event-list' => EventListResolver::class,
        'news' => NewsBlockResolver::class,
        'calendar' => CalendarBlockResolver::class,
    ];

    /** @return list<string> */
    public static function resolvableTypes(): array
    {
        return array_keys(self::RESOLVERS);
    }

    /**
     * @param  iterable<ContentPart>  $parts
     * @return array<int, array<string, mixed>>
     */
    public function resolveAll(iterable $parts, ResolutionContext $context): array
    {
        $byType = collect($parts)
            ->filter(fn (ContentPart $part) => isset(self::RESOLVERS[$part->type]))
            ->groupBy('type');

        $resolved = [];
        foreach ($byType as $type => $group) {
            /** @var Collection<int, ContentPart> $group */
            $resolver = app(self::RESOLVERS[$type]);
            $resolved += $resolver->resolve($group->keyBy('id'), $context);
        }

        return $resolved;
    }

    /**
     * Single-block resolution for the admin preview endpoint. Builds an unsaved
     * ContentPart and runs through the identical resolver a saved part would use, so
     * preview and public render can never diverge into two implementations.
     *
     * @param  array<string, mixed>  $jsonContent
     * @param  array<string, mixed>|null  $options
     * @return array<string, mixed>|null null when the type isn't resolvable
     */
    public function resolveOne(string $type, array $jsonContent, ?array $options, ResolutionContext $context): ?array
    {
        if (! isset(self::RESOLVERS[$type])) {
            return null;
        }

        $part = new ContentPart([
            'type' => $type,
            'json_content' => $jsonContent,
            'options' => $options,
        ]);
        // Never persisted — id is only a map key for this single call.
        $part->id = 0;

        $resolver = app(self::RESOLVERS[$type]);

        return $resolver->resolve(collect([0 => $part]), $context)[0] ?? null;
    }
}
