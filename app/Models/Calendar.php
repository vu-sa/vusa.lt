<?php

namespace App\Models;

use App\Enums\CalendarHeroStyleEnum;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use App\Services\IcalendarService;
use Datetime;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;
use Spatie\CalendarLinks\Link;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\SchemaOrg\Event;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\Place;

/**
 * @property int $id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property array|string|null $location
 * @property bool $is_remote
 * @property array|string|null $organizer
 * @property array|string|null $cto_url URL for Call To Action
 * @property string|null $facebook_url
 * @property string|null $video_url
 * @property string|null $main_image
 * @property bool $is_draft
 * @property bool $is_all_day
 * @property bool $is_international
 * @property CalendarHeroStyleEnum $hero_style
 * @property Carbon $date
 * @property Carbon|null $end_date
 * @property int|null $category_id
 * @property int $tenant_id
 * @property string|null $meeting_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int|null $registration_form_id
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Category|null $category
 * @property-read array $translatable_columns_from
 * @property-read mixed $main_image_url
 * @property-read MediaCollection<int, Media> $media
 * @property-read Meeting|null $meeting
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\CalendarFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar forLocale(string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Appends(['main_image_url'])]
#[Guarded(['id', 'created_at', 'updated_at'])]
#[Table(name: 'calendar')]
class Calendar extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, LogsModelActivity, Searchable, SoftDeletes;

    /**
     * Widest date range one calendar query may span — shared by
     * `Api\CalendarController` (the public timeline) and `EventListResolver` (the
     * `event-list` rich-content block's `range` mode), so the cap can't drift between
     * the two query paths that both window on `date`.
     */
    public const MAX_RANGE_DAYS = 455;

    #[\Override]
    protected function casts(): array
    {
        return [
            // IMPORTANT: just transform date always to datetime, don't keep as number, as problems arise
            'date' => 'datetime:Y-m-d H:i',
            'end_date' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'is_draft' => 'boolean',
            'is_all_day' => 'boolean',
            'is_international' => 'boolean',
            'is_remote' => 'boolean',
            'hero_style' => CalendarHeroStyleEnum::class,
        ];
    }

    /**
     * Scope events relevant to the given locale.
     * International events are always included; local-only events are excluded for 'en'.
     */
    public function scopeForLocale($query, string $locale)
    {
        return $locale === 'lt'
            ? $query
            : $query->where('is_international', 1);
    }

    /** Excludes drafts. Shared by every public-facing calendar listing (resolvers, controllers). */
    public function scopePublished($query)
    {
        return $query->where('is_draft', false);
    }

    /**
     * Restricts to one category, by alias. A no-op when `$alias` is null/empty — callers
     * don't need to guard the call themselves. The category is a grouping key, not a
     * publication gate — a trashed category (e.g. an old campaign) must still work as
     * one. See the identical rationale in PublicPageController::summerCamps().
     */
    public function scopeInCategoryAlias($query, ?string $alias)
    {
        if ($alias === null || $alias === '') {
            return $query;
        }

        return $query->whereHas('category', function ($q) use ($alias): void {
            $q->withTrashed()->where('alias', $alias);
        });
    }

    public $translatable = [
        'title',
        'description',
        'location',
        'organizer',
        'cto_url',
    ];

    /**
     * `description` is Tiptap `full` preset HTML (CalendarForm.vue), rendered with
     * `v-html` on the public event page (Pages/Public/CalendarEvent.vue).
     */
    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    /**
     * Get the main image URL from Spatie Media collection with fallback to legacy URL field.
     */
    protected function mainImageUrl(): Attribute
    {
        return Attribute::make(get: function () {
            // First try Spatie Media collection
            $mainImageMedia = $this->getFirstMedia('main_image');
            if ($mainImageMedia) {
                return $mainImageMedia->getUrl();
            }
            // Fallback to legacy main_image URL field (for backwards compatibility)
            if ($this->main_image) {
                return $this->main_image;
            }
            // Final fallback to first gallery image
            $firstMedia = $this->getFirstMedia('images');

            return $firstMedia?->getUrl();
        });
    }

    #[\Override]
    protected static function booted()
    {
        static::saved(function ($calendar): void {
            // Flush calendar cache for all locales since calendar events can be international
            Cache::tags(['calendar', 'locale_lt', 'locale_en'])->flush();
            // Also clear the specific iCal cache keys used by IcalendarService
            IcalendarService::clearCache();
        });

        static::deleted(function ($calendar): void {
            // Flush calendar cache for all locales since calendar events can be international
            Cache::tags(['calendar', 'locale_lt', 'locale_en'])->flush();
            // Also clear the specific iCal cache keys used by IcalendarService
            IcalendarService::clearCache();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The meeting this event announces, if it is a posėdis rather than a plain event.
     *
     * `withTrashed()`: meetings soft-delete, and the FK only nulls on a hard delete.
     *
     * @return BelongsTo<Meeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('main_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->useDisk('spatieMediaLibrary')
            ->withResponsiveImages();

        $this
            ->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
            ->useDisk('spatieMediaLibrary')
            ->withResponsiveImages();
    }

    /**
     * Register media conversions for WebP optimization.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(80)
            ->width(1600)
            ->performOnCollections('main_image', 'images') /** @phpstan-ignore method.notFound */
            ->nonQueued(); // Run synchronously for immediate availability
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->getTranslation('title', app()->getLocale()) ?: $this->getTranslation('title', 'lt') ?: $this->getTranslation('title', 'en'),
            'title_lt' => $this->getTranslation('title', 'lt'),
            'title_en' => $this->getTranslation('title', 'en'),
            'date' => $this->date->timestamp,
            'end_date' => $this->end_date ? $this->end_date->timestamp : null,
            'lang' => $this->lang ?? app()->getLocale(),
            'tenant_id' => $this->tenant_id,
            'tenant_ids' => [$this->tenant_id],
            'tenant_name' => $this->tenant->fullname,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        // Only index published (non-draft) calendar events
        return ! $this->trashed() && ! $this->is_draft;
    }

    /**
     * Get the engine used to index the model.
     * Calendar should use Typesense for public search.
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('typesense');
    }

    // TODO: add all pages to dev seed
    public function googleLink(): ?string
    {
        // check if event date is after end date, if so, return null
        // TODO: check in frontend
        if ($this->end_date && $this->date > $this->end_date) {
            return null;
        }

        return Link::create(
            $this->title,
            Datetime::createFromFormat('Y-m-d H:i:s', $this->date),
            $this->end_date
                ? Datetime::createFromFormat('Y-m-d H:i:s', $this->end_date)
                : Carbon::parse($this->date)->addHour()->toDateTime()
        )
            ->description(strip_tags($this->description))
            ->address($this->location ?? '')
            ->google();
    }

    /**
     * Generate Event structured data (JSON-LD) for this calendar event.
     */
    public function toEventSchema(): Event
    {
        $locale = app()->getLocale();
        $title = $this->getTranslation('title', $locale) ?: $this->title;

        $schema = (new Event)
            ->name($title)
            ->startDate($this->date)
            ->setProperty('eventStatus', 'https://schema.org/EventScheduled')
            ->organizer(
                (new Organization)
                    ->name($this->tenant->shortname ?? 'VU SA')
                    ->url(route('home', ['subdomain' => $this->tenant->alias]))
            );

        // Add end date if exists
        if ($this->end_date) {
            $schema->endDate($this->end_date);
        }

        // Add description
        $description = $this->getTranslation('description', $locale) ?: $this->description;
        if ($description) {
            $schema->description(strip_tags($description));
        }

        // Add location if exists
        $location = $this->getTranslation('location', $locale) ?: $this->location;
        if ($location) {
            $schema->location(
                (new Place)
                    ->name($location)
                    ->address($location)
            );
        }

        // Add event attendance mode. The explicit flag wins; an empty location on an older
        // row (from before is_remote existed) still falls back to the old inference.
        if ($this->is_remote) {
            $schema->setProperty('eventAttendanceMode', 'https://schema.org/OnlineEventAttendanceMode');
        } elseif ($location) {
            $schema->setProperty('eventAttendanceMode', 'https://schema.org/OfflineEventAttendanceMode');
        } else {
            $schema->setProperty('eventAttendanceMode', 'https://schema.org/OnlineEventAttendanceMode');
        }

        // Add image if exists
        $imageUrl = $this->getFirstMediaUrl('images') ?: $this->getFirstMediaUrl('main_image');
        if ($imageUrl) {
            $schema->image($imageUrl);
        }

        return $schema;
    }
}
