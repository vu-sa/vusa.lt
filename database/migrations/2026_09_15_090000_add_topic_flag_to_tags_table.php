<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->boolean('is_topic')->default(false)->after('alias');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_topic');
        });

        // `tags.alias` had no DB-level uniqueness — only Tag::generateAlias()'s app-level loop —
        // and at least one row (`ISF`) isn't a slug. Normalise and dedupe before the index can land.
        $tags = DB::table('tags')->orderBy('id')->get(['id', 'alias', 'name']);
        $seenAliases = [];

        foreach ($tags as $tag) {
            $name = json_decode((string) $tag->name, true);
            $base = $tag->alias !== null && $tag->alias !== ''
                ? Str::slug($tag->alias)
                : Str::slug($name['lt'] ?? $name['en'] ?? 'tag-'.$tag->id);

            if ($base === '') {
                $base = 'tag-'.$tag->id;
            }

            $alias = $base;
            $counter = 1;

            while (isset($seenAliases[$alias])) {
                $alias = $base.'-'.$counter;
                $counter++;
            }

            $seenAliases[$alias] = true;

            if ($alias !== $tag->alias) {
                DB::table('tags')->where('id', $tag->id)->update(['alias' => $alias]);
            }
        }

        Schema::table('tags', function (Blueprint $table): void {
            $table->string('alias', 255)->nullable(false)->change();
            $table->unique('alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->dropUnique(['alias']);
            $table->string('alias', 255)->nullable()->change();
            $table->dropColumn(['is_topic', 'sort_order']);
        });
    }
};
