<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const string SUSIRINKIMAS_PATTERN = '/susirinkim/iu';

    /** @var array<int, array{0: string, 1: string}> */
    private const array TITLE_RULES = [
        ['stovykla', '/stovykl|camp/iu'],
        ['konferencija', '/konferencij|ataskaitin.{0,3}-rinkimin/iu'],
        ['posedis', '/posėd/iu'],
        ['rinkimai', '/rinkim|kandidat|balsav/iu'],
        ['mokymai', '/mokym|seminar|dirbtuv|workshop|training/iu'],
        ['atstovavimas', '/susitik|dalyvau|darbo grup|rektorat|senat|komisij/iu'],
        ['terminas', '/registracij|terminas|paraišk|deadline/iu'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $requiredSlugs = [...array_column(self::TITLE_RULES, 0), 'susirinkimas'];
        $eventTypeIds = DB::table('event_types')
            ->whereIn('slug', $requiredSlugs)
            ->pluck('id', 'slug');

        foreach ($requiredSlugs as $slug) {
            if (! $eventTypeIds->has($slug)) {
                throw new RuntimeException("Missing seeded event type '{$slug}'.");
            }
        }

        DB::table('calendar')
            ->whereNull('event_type_id')
            ->select(['id', 'title', 'meeting_id'])
            ->chunkById(500, function (Collection $events) use ($eventTypeIds): void {
                /** @var array<int, list<int>> $eventIdsByType */
                $eventIdsByType = [];

                foreach ($events as $event) {
                    $slug = $this->resolveSlug($event);

                    if ($slug === null) {
                        continue;
                    }

                    $eventIdsByType[$eventTypeIds[$slug]][] = $event->id;
                }

                foreach ($eventIdsByType as $eventTypeId => $eventIds) {
                    DB::table('calendar')
                        ->whereIn('id', $eventIds)
                        ->whereNull('event_type_id')
                        ->update(['event_type_id' => $eventTypeId]);
                }
            });
    }

    /**
     * Existing assignments cannot be distinguished from backfilled ones.
     */
    public function down(): void {}

    private function resolveSlug(object $event): ?string
    {
        $title = json_decode((string) $event->title, true);

        $titleLt = is_array($title) && is_string($title['lt'] ?? null) ? $title['lt'] : '';
        $titleEn = is_array($title) && is_string($title['en'] ?? null) ? $title['en'] : '';
        $haystack = $titleLt."\n".$titleEn;

        if (preg_match(self::SUSIRINKIMAS_PATTERN, $haystack) === 1) {
            return 'susirinkimas';
        }

        if ($event->meeting_id !== null) {
            return 'posedis';
        }

        foreach (self::TITLE_RULES as [$slug, $pattern]) {
            if (preg_match($pattern, $haystack) === 1) {
                return $slug;
            }
        }

        return null;
    }
};
