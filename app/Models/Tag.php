<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $alias
 * @property bool $is_topic
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property array|string|null $name
 * @property array|string|null $description
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Calendar> $calendars
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, News> $news
 * @property-read Collection<int, Page> $pages
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\TagFactory factory($count = null, $state = [])
 * @method static Builder<static>|Tag newModelQuery()
 * @method static Builder<static>|Tag newQuery()
 * @method static Builder<static>|Tag onlyTrashed()
 * @method static Builder<static>|Tag query()
 * @method static Builder<static>|Tag topics()
 * @method static Builder<static>|Tag whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Tag whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Tag whereLocale(string $column, string $locale)
 * @method static Builder<static>|Tag whereLocales(string $column, array $locales)
 * @method static Builder<static>|Tag withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Tag withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'description',
    'alias',
    'is_topic',
    'sort_order',
])]
class Tag extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public $translatable = ['name', 'description'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'is_topic' => 'boolean',
        ];
    }

    /**
     * The models a tag may be attached to, and the relation each exposes for it. The single
     * source of truth for "what is taggable" — mirrors Type::TYPEABLE_RELATIONS, and anything
     * that resolves a taggable relation from stored/request data (e.g. TopicController
     * aggregating content for a topic page) must read this list rather than hardcode model
     * classes, so a future taggable model can't be reached without being added here first.
     *
     * @var array<string, string>
     */
    public const TAGGABLE_RELATIONS = [
        'news' => 'news',
        'page' => 'pages',
        'calendar' => 'calendars',
    ];

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        // Auto-generate alias if not provided
        static::saving(function ($tag): void {
            if (empty($tag->alias)) {
                $tag->alias = $tag->generateAlias();
            }
        });

        static::saved(fn () => Cache::forget('all-tags-for-inertia'));
        static::deleted(fn () => Cache::forget('all-tags-for-inertia'));
        static::restored(fn () => Cache::forget('all-tags-for-inertia'));

        // Force-deleting a tag cascades the `taggables.tag_id` FK automatically — no manual
        // detach needed here (contrast News/Page/Calendar, whose side of the morph pivot has
        // no DB-level FK to cascade through).
    }

    /**
     * Only tags flagged as navigable topics — the rest stay descriptive-only labels.
     */
    public function scopeTopics(Builder $query): Builder
    {
        return $query->where('is_topic', true);
    }

    public function news(): MorphToMany
    {
        return $this->morphedByMany(News::class, 'taggable');
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'taggable');
    }

    public function calendars(): MorphToMany
    {
        return $this->morphedByMany(Calendar::class, 'taggable');
    }

    /**
     * Generate a URL-friendly alias from the tag name
     */
    protected function generateAlias(): string
    {
        // Get the Lithuanian name first, fallback to English, then to any available translation
        $name = $this->getTranslation('name', 'lt')
            ?? $this->getTranslation('name', 'en')
            ?? (is_array($this->name) ? collect($this->name)->first() : $this->name);

        if (empty($name)) {
            // Fallback to ID-based alias if no name is available
            return 'tag-'.($this->id ?? uniqid());
        }

        // Create a URL-friendly slug
        $baseAlias = Str::slug($name);

        // Ensure uniqueness by checking for existing aliases
        $alias = $baseAlias;
        $counter = 1;

        // Trashed tags are included: a restored tag must not come back sharing an
        // alias with a tag created while it was in the trash.
        while (static::withTrashed()->where('alias', $alias)->where('id', '!=', $this->id ?? 0)->exists()) {
            $alias = $baseAlias.'-'.$counter;
            $counter++;
        }

        return $alias;
    }
}
