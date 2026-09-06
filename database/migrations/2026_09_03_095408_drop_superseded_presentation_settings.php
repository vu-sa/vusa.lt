<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drops presentation settings the public redesign now decides.
 *
 * Both were authorable choices that the design language settles centrally, so leaving them in
 * the data means an author can still opt a block out of the system:
 *
 * - Hero/hero-carousel button `color` (red|yellow|zinc|white). A call to action is the brand
 *   fill — VU SA red on the light canvas, amber on near-black — and `variant` (default/outline)
 *   still carries the primary/secondary distinction, which is the one that means something.
 * - Navigation root `extra_attributes.menu_width`. The mega menu spans the header measure
 *   regardless; no row ever set this, but the form offered it.
 *
 * Irreversible on purpose: `down()` cannot invent which colour each button used to be, and
 * restoring a default would be a different site from the one that was migrated.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropButtonColours();
        $this->dropMenuWidth();
    }

    /**
     * Rewrites `json_content` in PHP rather than SQL: the column holds two different shapes
     * (`hero` is an object with `buttons`, `hero-carousel` an array of slides that each have
     * `buttons`), and a JSON path expression would have to hardcode slide indexes.
     */
    private function dropButtonColours(): void
    {
        DB::table('content_parts')
            ->whereIn('type', ['hero', 'hero-carousel'])
            ->where('json_content', 'like', '%"color"%')
            ->orderBy('id')
            ->chunkById(200, function ($parts): void {
                foreach ($parts as $part) {
                    $decoded = json_decode((string) $part->json_content, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    $cleaned = array_is_list($decoded)
                        ? array_map($this->stripButtonColours(...), $decoded)
                        : $this->stripButtonColours($decoded);

                    DB::table('content_parts')
                        ->where('id', $part->id)
                        ->update(['json_content' => json_encode($cleaned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private function stripButtonColours(array $block): array
    {
        if (! isset($block['buttons']) || ! is_array($block['buttons'])) {
            return $block;
        }

        $block['buttons'] = array_map(static function ($button) {
            if (is_array($button)) {
                unset($button['color']);
            }

            return $button;
        }, $block['buttons']);

        return $block;
    }

    private function dropMenuWidth(): void
    {
        DB::table('navigation')
            ->where('extra_attributes', 'like', '%menu_width%')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $attributes = json_decode((string) $row->extra_attributes, true);

                    if (! is_array($attributes)) {
                        continue;
                    }

                    unset($attributes['menu_width']);

                    DB::table('navigation')
                        ->where('id', $row->id)
                        ->update(['extra_attributes' => json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            });
    }
};
