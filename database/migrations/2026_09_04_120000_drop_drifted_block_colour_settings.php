<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops per-block presentation options that have drifted from the design system —
 * colour/variant pickers that predate the brand-token palette (`--brand`/`--brand-fill`),
 * plus one regression: the sibling migration
 * (2026_09_03_095408_drop_superseded_presentation_settings.php) already stripped hero's
 * button `color`, but `hero-carousel` shipped the same picker afterwards and kept writing
 * it. Same shape as that migration: irreversible (`down()` cannot invent which colour a
 * card or button used to be), chunked raw `DB::table()` writes (bypasses `ContentPart`'s
 * `saving` hooks and the activity log on purpose).
 *
 * Pre-flight census (2026-09-04, production data):
 *   shadcn-card:          variant=outline(15) variant=soft(19) variant=null(1)
 *                         color=zinc(24) color=red(9) color=yellow(3)
 *   number-stat-section:  color=zinc(5) color=red(1)
 *   hero/content-grid/carousel-slide-deck/photo-gallery decorations: only vusa-red/
 *                         vusa-yellow observed, opacity 40-60, rotation on 1 row.
 *   hero-carousel:        buttons carry no `color` key in the current dataset — this
 *                         branch is a no-op today, kept for defensive coverage and other
 *                         environments.
 *
 * Dropped:
 * - shadcn-card: options.color, options.variant, options.isTitleColored, options.showIcon
 *   (already @deprecated, dead on display).
 * - number-stat-section: options.color — also the source of RCNumberSection.vue's
 *   `` `text-vusa-${color}` `` dynamically-interpolated class, which the Tailwind JIT
 *   compiler can never extract regardless.
 * - hero / content-grid / carousel-slide-deck / photo-gallery: color/opacity/rotation off
 *   any decoration object (hero.options.imageDecorations, or json_content[*].decorations /
 *   content-grid's nested json_content[row].columns[col].content.decorations) — decorative
 *   accents are a single fixed brand treatment now (see DecorativeElement.vue).
 * - hero-carousel: json_content[*].buttons[*].color.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropCardChrome();
        $this->dropStatColour();
        $this->dropDecorationChrome();
        $this->dropCarouselButtonColours();
    }

    private function dropCardChrome(): void
    {
        DB::table('content_parts')
            ->where('type', 'shadcn-card')
            ->where(fn ($query) => $query
                ->where('options', 'like', '%"color"%')
                ->orWhere('options', 'like', '%"variant"%')
                ->orWhere('options', 'like', '%"isTitleColored"%')
                ->orWhere('options', 'like', '%"showIcon"%'))
            ->orderBy('id')
            ->chunkById(200, function ($parts): void {
                foreach ($parts as $part) {
                    $decoded = json_decode((string) $part->options, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    unset($decoded['color'], $decoded['variant'], $decoded['isTitleColored'], $decoded['showIcon']);

                    DB::table('content_parts')
                        ->where('id', $part->id)
                        ->update(['options' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }

    private function dropStatColour(): void
    {
        DB::table('content_parts')
            ->where('type', 'number-stat-section')
            ->where('options', 'like', '%"color"%')
            ->orderBy('id')
            ->chunkById(200, function ($parts): void {
                foreach ($parts as $part) {
                    $decoded = json_decode((string) $part->options, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    unset($decoded['color']);

                    DB::table('content_parts')
                        ->where('id', $part->id)
                        ->update(['options' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }

    /**
     * Decorations live at different depths per type (`hero.options.imageDecorations` is
     * a flat array; `content-grid`'s live one level deeper inside each row's columns;
     * `carousel-slide-deck`/`photo-gallery` carry theirs per json_content item) — walked
     * recursively instead of four hardcoded paths, matching on the decoration shape
     * itself (`type`+`position`+`size`) rather than a fixed key path. This also means it
     * never touches a tiptap `rcTag` mark's own unrelated `color` attribute, which has a
     * different shape (`{type: 'rcTag', attrs: {variant, color}}`, no `position`/`size`).
     */
    private function dropDecorationChrome(): void
    {
        DB::table('content_parts')
            ->whereIn('type', ['hero', 'content-grid', 'carousel-slide-deck', 'photo-gallery'])
            ->where(fn ($query) => $query
                ->where('options', 'like', '%"color"%')
                ->orWhere('options', 'like', '%"opacity"%')
                ->orWhere('options', 'like', '%"rotation"%')
                ->orWhere('json_content', 'like', '%"color"%')
                ->orWhere('json_content', 'like', '%"opacity"%')
                ->orWhere('json_content', 'like', '%"rotation"%'))
            ->orderBy('id')
            ->chunkById(200, function ($parts): void {
                foreach ($parts as $part) {
                    $optionsChanged = false;
                    $contentChanged = false;

                    $options = json_decode((string) $part->options, true);
                    if (is_array($options)) {
                        $options = $this->stripDecorationChrome($options, $optionsChanged);
                    }

                    $content = json_decode((string) $part->json_content, true);
                    if (is_array($content)) {
                        $content = $this->stripDecorationChrome($content, $contentChanged);
                    }

                    if (! $optionsChanged && ! $contentChanged) {
                        continue;
                    }

                    $update = [];
                    if ($optionsChanged) {
                        $update['options'] = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    if ($contentChanged) {
                        $update['json_content'] = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }

                    DB::table('content_parts')->where('id', $part->id)->update($update);
                }
            });
    }

    /**
     * @param  array<array-key, mixed>  $node
     * @param  bool  $changed  Set true (by reference) the first time a decoration key is unset anywhere in the tree.
     * @return array<array-key, mixed>
     */
    private function stripDecorationChrome(array $node, bool &$changed): array
    {
        $isDecoration = isset($node['type'], $node['position'], $node['size'])
            && (array_key_exists('color', $node) || array_key_exists('opacity', $node) || array_key_exists('rotation', $node));

        if ($isDecoration) {
            unset($node['color'], $node['opacity'], $node['rotation']);
            $changed = true;
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->stripDecorationChrome($value, $changed);
            }
        }

        return $node;
    }

    private function dropCarouselButtonColours(): void
    {
        DB::table('content_parts')
            ->where('type', 'hero-carousel')
            ->where('json_content', 'like', '%"color"%')
            ->orderBy('id')
            ->chunkById(200, function ($parts): void {
                foreach ($parts as $part) {
                    $decoded = json_decode((string) $part->json_content, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    $cleaned = array_map($this->stripButtonColours(...), $decoded);

                    DB::table('content_parts')
                        ->where('id', $part->id)
                        ->update(['json_content' => json_encode($cleaned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $slide
     * @return array<string, mixed>
     */
    private function stripButtonColours(array $slide): array
    {
        if (! isset($slide['buttons']) || ! is_array($slide['buttons'])) {
            return $slide;
        }

        $slide['buttons'] = array_map(static function ($button) {
            if (is_array($button)) {
                unset($button['color']);
            }

            return $button;
        }, $slide['buttons']);

        return $slide;
    }
};
