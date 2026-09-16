<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Old category alias => destination topic tag alias. `grey` ("Kita informacija") is
     * deliberately absent — it carries no real meaning and those rows simply lose the value.
     *
     * @var array<string, string>
     */
    private const array TOPIC_MAP = [
        'red' => 'akademine-informacija',
        'yellow' => 'socialine-informacija',
        'stipendijos' => 'finansine-parama-stipendijos',
        'vu-sa-dokumentai' => 'vu-sa-dokumentai',
    ];

    /**
     * Old category alias => destination event type slug.
     *
     * @var array<string, string>
     */
    private const array EVENT_TYPE_MAP = [
        'freshmen-camps' => 'stovykla',
        'vu-sa-conferences' => 'konferencija',
    ];

    /**
     * Run the migrations.
     *
     * Not wrapped in an explicit `DB::transaction()` — the migrator already runs the whole
     * method inside one for databases that support transactional DDL (SQLite, this repo's
     * test suite included), and nesting a second transaction around `Schema::table()`'s own
     * SQLite table-rebuild strategy (used for `dropForeign()`) corrupts the rebuild.
     */
    public function up(): void
    {
        $now = now();

        foreach ([
            ['socialine-informacija', 'Socialinė informacija', 'Social information'],
            ['vu-sa-dokumentai', 'VU SA dokumentai', 'VU SA documents'],
        ] as [$alias, $lt, $en]) {
            DB::table('tags')->updateOrInsert(
                ['alias' => $alias],
                [
                    'name' => json_encode(['lt' => $lt, 'en' => $en]),
                    'is_topic' => true,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $categoryIds = DB::table('categories')->pluck('id', 'alias');
        $tagIds = DB::table('tags')->whereIn('alias', array_values(self::TOPIC_MAP))->pluck('id', 'alias');
        $eventTypeIds = DB::table('event_types')->whereIn('slug', array_values(self::EVENT_TYPE_MAP))->pluck('id', 'slug');

        foreach (self::TOPIC_MAP as $categoryAlias => $tagAlias) {
            $categoryId = $categoryIds[$categoryAlias] ?? null;
            $tagId = $tagIds[$tagAlias] ?? null;

            if ($categoryId === null || $tagId === null) {
                continue;
            }

            foreach (['news' => 'news', 'pages' => 'page'] as $table => $taggableType) {
                DB::table($table)->where('category_id', $categoryId)->orderBy('id')
                    ->pluck('id')
                    ->chunk(500)
                    ->each(function ($ids) use ($tagId, $taggableType, $now): void {
                        DB::table('taggables')->insertOrIgnore($ids->map(fn ($id) => [
                            'tag_id' => $tagId,
                            'taggable_type' => $taggableType,
                            'taggable_id' => $id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all());
                    });
            }
        }

        // Only fills rows the title-based backfill migration left NULL — never overwrites
        // an event type determined more precisely than the former category.
        foreach (self::EVENT_TYPE_MAP as $categoryAlias => $eventTypeSlug) {
            $categoryId = $categoryIds[$categoryAlias] ?? null;
            $eventTypeId = $eventTypeIds[$eventTypeSlug] ?? null;

            if ($categoryId === null || $eventTypeId === null) {
                continue;
            }

            DB::table('calendar')
                ->where('category_id', $categoryId)
                ->whereNull('event_type_id')
                ->update(['event_type_id' => $eventTypeId]);
        }

        // Only two `event-list` content_parts rows reference `categoryAlias` in
        // production data (both `freshmen-camps`) — rewrite them to the event-type key
        // the resolvers read going forward.
        DB::table('content_parts')
            ->where('type', 'event-list')
            ->whereNotNull('options')
            ->orderBy('id')
            ->get(['id', 'options'])
            ->each(function (object $part): void {
                $options = json_decode((string) $part->options, true);
                if (! is_array($options) || ($options['categoryAlias'] ?? null) !== 'freshmen-camps') {
                    return;
                }

                unset($options['categoryAlias']);
                $options['eventTypeSlug'] = 'stovykla';

                DB::table('content_parts')->where('id', $part->id)->update([
                    'options' => json_encode($options),
                ]);
            });

        // `news.category_id`'s FK-named index ('news_category_id_foreign', from the 2023
        // baseline migration) never became a real foreign key on SQLite — SQLite can't add
        // a FK to a table via ALTER TABLE, only at CREATE TABLE time, so `foreign()` calls
        // added later against the already-created `news` table silently produced nothing
        // but a plain index. `dropForeign()` has no constraint to find there and is a
        // no-op, so the index survives and blocks the native `DROP COLUMN` unless dropped
        // explicitly by name. `dropForeign()` still runs first for MySQL, which does hold a
        // real constraint by that name.
        Schema::table('news', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropIndex('news_category_id_foreign');
            $table->dropColumn('category_id');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('category_id');
        });

        // Same SQLite quirk as `news` above. `calendar`'s index kept its pre-rename name,
        // `calendar_category_foreign` (from when the column itself was named `category`,
        // before `2024_12_03_185609_fix_category_relations` renamed it to `category_id` —
        // SQLite's `renameColumn` doesn't rename dependent index names).
        Schema::table('calendar', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropIndex('calendar_category_foreign');
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('categories');
    }

    /**
     * Structural rollback only — matches every other destructive migration in this repo.
     * Does not attempt to reconstruct which row used to carry which category.
     */
    public function down(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('alias')->nullable()->unique();
            $table->timestamps();
            $table->longText('name')->nullable();
            $table->longText('description')->nullable();
            $table->softDeletes();
        });

        Schema::table('news', function (Blueprint $table): void {
            $table->unsignedInteger('category_id')->nullable()->after('tenant_id');
            $table->foreign('category_id')->references('id')->on('categories');
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->unsignedInteger('category_id')->nullable()->after('tenant_id');
        });

        Schema::table('calendar', function (Blueprint $table): void {
            $table->unsignedInteger('category_id')->nullable()->after('tenant_id');
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }
};
