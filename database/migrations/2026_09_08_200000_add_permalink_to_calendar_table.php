<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->longText('permalink')->nullable()->after('title');
        });

        $this->backfillPermalinks();
    }

    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('permalink');
        });
    }

    /**
     * One-time seed of `calendar.permalink` from each event's title, scoped to its year — most
     * title collisions in this data are the same event recurring annually, which putting the
     * year in the route (not stored, derived from `date` at request time) already disambiguates.
     * A same-year collision gets `-2`, `-3`, … . Nothing here touches `public_urls`: that table
     * only ever gets a row once an admin actually changes a permalink after this.
     */
    private function backfillPermalinks(): void
    {
        /** @var array<string, true> $usedSlugs keyed "year|locale|slug" */
        $usedSlugs = [];

        DB::table('calendar')->orderBy('id')->each(function (object $calendar) use (&$usedSlugs): void {
            $year = (new DateTime($calendar->date))->format('Y');
            /** @var array<string, mixed> $titles */
            $titles = json_decode((string) $calendar->title, true) ?: [];
            $permalinks = [];

            foreach (['lt', 'en'] as $locale) {
                $title = is_string($titles[$locale] ?? null) ? trim($titles[$locale]) : '';

                if ($title === '') {
                    continue;
                }

                $permalinks[$locale] = $this->nextSlug($year, $locale, $title, $usedSlugs);
            }

            if ($permalinks === []) {
                return;
            }

            DB::table('calendar')->where('id', $calendar->id)->update([
                'permalink' => json_encode($permalinks),
            ]);
        });
    }

    /** @param array<string, true> $usedSlugs */
    private function nextSlug(string $year, string $locale, string $title, array &$usedSlugs): string
    {
        $baseSlug = rtrim(Str::limit(Str::slug($title), 100, ''), '-');
        $baseSlug = $baseSlug === '' ? 'event' : $baseSlug;
        $slug = $baseSlug;
        $suffix = 2;

        while (isset($usedSlugs["{$year}|{$locale}|{$slug}"])) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        $usedSlugs["{$year}|{$locale}|{$slug}"] = true;

        return $slug;
    }
};
