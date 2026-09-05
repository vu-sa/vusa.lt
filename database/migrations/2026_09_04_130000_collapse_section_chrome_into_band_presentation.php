<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Collapses the old per-block `background`/`padding`/`rounded`/`divider`/`bleed` authoring
 * surface into the new automatic band alternation (see `resources/js/Components/RichContent/
 * bandLayout.ts`) — a band's ground is now computed from its position in the document, and the
 * only ground override left is `options.presentation` (`auto`/`plain`). Plain sections may
 * retain their legacy vertical rhythm through `options.plainPadding`.
 *
 * Preserves *intent*, not pixels: a block that had a ground still gets one somewhere in the new
 * rhythm (`auto`); a block with none stays chrome-free (`plain`). A `contrast`/`gradient` row
 * becomes `auto` and may land on either alternation slot depending on final page position — a
 * frozen per-row tint backfill would defeat the point of the change. A visual review pass over
 * the affected rows is expected instead, per the plan's own gate.
 *
 * Irreversible on purpose, same shape as the two precedent migrations on this branch
 * (2026_09_03_095408_drop_superseded_presentation_settings.php,
 * 2026_09_04_120000_drop_drifted_block_colour_settings.php): `down()` cannot invent which
 * background each row used to have. Chunked raw `DB::table()` writes bypass `ContentPart`'s
 * `saving` hooks and the activity log on purpose.
 *
 * Pre-flight census (2026-09-04, production data — `bg` is the stored `options.background`,
 * `(unset)` rows resolve to their type's previous rendered default):
 *   card-stack:            muted(2) → auto
 *   carousel-slide-deck:   none(2) → plain
 *   content-grid:          (unset)(3) none(2) → plain (type defaults to 'none')
 *   event-list:            none(2) → plain
 *   hero:                  (unset)(4) → auto (defaults to 'muted'); none(4) → plain
 *   link-list:             none(4) → plain
 *   number-stat-section:   (unset)(4) none(2) → plain (type defaults to 'none')
 *   photo-gallery:         none(2) → plain
 *   section:               contrast(2) → auto; none(2) → plain
 *   shadcn-accordion:      (unset)(65) muted(6) → auto (defaults to 'muted')
 *   spotify-embed:         (unset)(20) → auto (defaults to 'muted'); gradient(1) → auto
 *   process-steps, person-quote, cta-band: no rows in production today; handled defensively
 *   for other environments and future rows. cta-band never had background/padding/rounded/
 *   divider — only `bleed`, dropped without ever gaining `presentation` (see below).
 *   No row in any environment had `background: 'brand'` or `'ink'`; those defensive values
 *   now join the automatic rhythm because CTA bands exclusively own the emphasis treatment.
 */
return new class extends Migration
{
    /** Types whose display defaulted to a `muted` ground when `options.background` was unset. */
    private const array MUTED_DEFAULT_TYPES = ['shadcn-accordion', 'hero', 'card-stack', 'spotify-embed'];

    private const array SCOPED_TYPES = [
        'shadcn-accordion', 'number-stat-section', 'content-grid', 'carousel-slide-deck',
        'card-stack', 'photo-gallery', 'link-list', 'event-list', 'section', 'process-steps',
        'person-quote', 'hero', 'spotify-embed', 'cta-band',
    ];

    public function up(): void
    {
        DB::table('content_parts')
            ->whereIn('type', self::SCOPED_TYPES)
            ->orderBy('id')
            ->chunkById(200, function ($parts): void {
                foreach ($parts as $part) {
                    $options = json_decode((string) $part->options, true);

                    if (! is_array($options)) {
                        continue;
                    }

                    $collapsed = $this->collapseChrome($part->type, $options);

                    if ($collapsed === null) {
                        continue;
                    }

                    DB::table('content_parts')
                        ->where('id', $part->id)
                        ->update(['options' => json_encode($collapsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }

    /**
     * @param  array<array-key, mixed>  $options
     * @return array<array-key, mixed>|null Null when this row carried none of the dropped keys.
     */
    private function collapseChrome(string $type, array $options): ?array
    {
        $hadChrome = array_key_exists('background', $options) || array_key_exists('padding', $options)
            || array_key_exists('rounded', $options) || array_key_exists('divider', $options)
            || array_key_exists('bleed', $options);

        if (! $hadChrome) {
            return null;
        }

        $background = $options['background'] ?? null;
        $padding = $options['padding'] ?? null;
        unset($options['background'], $options['padding'], $options['rounded'], $options['divider'], $options['bleed']);

        // cta-band has no presentation control at all — it is always implicitly the one loud
        // emphasis band a page gets (see RCCtaBand/CtaBandDisplay.vue), so it only ever loses
        // `bleed` here and never gains a `presentation` key.
        if ($type === 'cta-band') {
            return $options;
        }

        $effectiveBackground = $background ?? (in_array($type, self::MUTED_DEFAULT_TYPES, true) ? 'muted' : 'none');

        $presentation = match ($effectiveBackground) {
            'none' => 'plain',
            default => null, // muted|contrast|gradient — the new default rhythm; key omitted (auto)
        };

        if ($presentation !== null) {
            $options['presentation'] = $presentation;
            $options['plainPadding'] = match ($padding) {
                'sm', 'md' => 'compact',
                'lg' => 'default',
                default => 'default',
            };
        }

        return $options;
    }
};
