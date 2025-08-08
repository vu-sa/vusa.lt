<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Datetime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Spatie\CalendarLinks\Link;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property array|string|null $location
 * @property array|string|null $organizer
 * @property array|string|null $cto_url URL for Call To Action
 * @property string|null $facebook_url
 * @property string|null $video_url
 * @property bool $is_draft
 * @property int $is_all_day
 * @property int $is_international
 * @property Carbon $date
 * @property Carbon|null $end_date
 * @property int|null $category_id
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int|null $registration_form_id
 * @property-read \App\Models\Category|null $category
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read \App\Models\Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\CalendarFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calendar whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class Calendar extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, Searchable;

    protected $table = 'calendar';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        // IMPORTANT: just transform date always to datetime, don't keep as number, as problems arise
        'date' => 'datetime:Y-m-d H:i',
        'end_date' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'is_draft' => 'boolean',
    ];

    public $translatable = [
        'title',
        'description',
        'location',
        'organizer',
        'cto_url',
    ];

    protected static function booted()
    {
        static::saved(function ($calendar) {
            // Flush calendar cache for all locales since calendar events can be international
            Cache::tags(['calendar', 'locale_lt', 'locale_en'])->flush();
        });

        static::deleted(function ($calendar) {
            // Flush calendar cache for all locales since calendar events can be international
            Cache::tags(['calendar', 'locale_lt', 'locale_en'])->flush();
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png'])
            ->useDisk('spatieMediaLibrary')
            ->withResponsiveImages();
    }

    public function toSearchableArray()
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->getTranslation('title', app()->getLocale()) ?: $this->getTranslation('title', 'lt') ?: $this->getTranslation('title', 'en'),
            'title_lt' => $this->getTranslation('title', 'lt'),
            'title_en' => $this->getTranslation('title', 'en'),
            'date' => $this->date->timestamp,
            'end_date' => $this->end_date ? $this->end_date->timestamp : null,
            'lang' => $this->lang ?? app()->getLocale(),
            'tenant_name' => $this->tenant->fullname,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        // Only index published (non-draft) calendar events
        return ! $this->is_draft;
    }

    /**
     * Get the engine used to index the model.
     * Calendar should use Typesense for public search.
     */
    public function searchableUsing()
    {
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
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
            DateTime::createFromFormat('Y-m-d H:i:s', $this->date),
            $this->end_date
                ? DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date)
                : Carbon::parse($this->date)->addHour()->toDateTime()
        )
            ->description(strip_tags($this->description))
            ->address($this->location ?? '')
            ->google();
    }
}
