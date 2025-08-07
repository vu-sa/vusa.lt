<?php

namespace App\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Actions\GetResourceManagers;
use App\Collections\ReservationCollection;
use App\Models\Pivots\ReservationResource;
use App\Models\Traits\HasTranslations;
use App\Services\ResourceCapacityCalculator;
use App\ValueObjects\TimeRange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string|null $identifier
 * @property array|string $name
 * @property array|string|null $description
 * @property int|null $resource_category_id
 * @property string|null $location
 * @property int $capacity
 * @property int $tenant_id
 * @property int $is_reservable
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read ReservationResource|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $active_reservations
 * @property-read \App\Models\ResourceCategory|null $category
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read \App\Models\Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\ResourceFactory factory($count = null, $state = [])
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Resource newModelQuery()
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Resource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resource onlyTrashed()
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Resource query()
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Resource whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Resource whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Resource whereLocale(string $column, string $locale)
 * @method static \AjCastro\EagerLoadPivotRelations\EagerLoadPivotBuilder<static>|Resource whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resource withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Resource withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Resource extends Model implements HasMedia
{
    use EagerLoadPivotTrait, HasFactory, HasTranslations, HasUlids, InteractsWithMedia, Searchable, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'location', 'capacity', 'is_reservable',
        'tenant_id', 'resource_category_id', 'media',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'capacity' => 'integer',
        'is_reservable' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public $translatable = ['name', 'description'];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png'])
            ->useDisk('spatieMediaLibrary');
    }

    public function toSearchableArray(): array
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
            'description->'.app()->getLocale() => $this->getTranslation('description', app()->getLocale()),
        ];
    }

    /**
     * Scope to filter only reservable resources
     */
    public function scopeReservable($query)
    {
        return $query->where('is_reservable', true);
    }

    /**
     * Get all reservations for this resource
     */
    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class)
            ->using(ReservationResource::class)
            ->withPivot(['state', 'start_time', 'end_time', 'quantity']);
    }

    /**
     * Get all reservations as a typed collection
     */
    public function getReservationsCollection(): ReservationCollection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Reservation> $reservations */
        $reservations = $this->reservations()->get();

        return new ReservationCollection($reservations->all());
    }

    /**
     * Get active reservations (created, reserved, or lent)
     */
    public function active_reservations(): BelongsToMany
    {
        return $this->reservations()->wherePivotIn('state', ['created', 'reserved', 'lent']);
    }

    public function managers(): \Illuminate\Support\Collection
    {
        return GetResourceManagers::execute($this);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ResourceCategory::class, 'resource_category_id');
    }

    /**
     * Calculate available capacity at a specific time
     *
     * @param  \Carbon\Carbon|string  $datetime  The time to check capacity at
     * @param  string  $symbol_start  Comparison operator for start time ('<', '<=')
     * @param  string  $symbol_end  Comparison operator for end time ('>', '>=')
     * @param  array<int, string>  $exceptReservations  Reservation IDs to exclude from calculation
     * @param  array<int, string>  $exceptResources  Resource IDs to exclude from calculation
     * @return int Available capacity at the specified time
     */
    public function leftCapacityAtTime(
        \Carbon\Carbon|string $datetime,
        string $symbol_start = '<',
        string $symbol_end = '>=',
        array $exceptReservations = [],
        array $exceptResources = []
    ): int {
        $calculator = new ResourceCapacityCalculator($this);
        $time = $datetime instanceof \Carbon\Carbon ? $datetime : \Carbon\Carbon::parse($datetime);

        return $calculator->calculateLeftCapacityAtTime(
            $time,
            $symbol_start,
            $symbol_end,
            $exceptReservations,
            $exceptResources
        );
    }

    /**
     * Calculate capacity before and after a specific time
     *
     * @param  \Carbon\Carbon|string  $datetime  The time to check capacity at
     * @param  array<int, string>  $exceptReservations  Reservation IDs to exclude from calculation
     * @param  array<int, string>  $exceptResources  Resource IDs to exclude from calculation
     * @return array{before: int, after: int} Capacity before and after the specified time
     */
    public function leftCapacityAtTimeArray(
        \Carbon\Carbon|string $datetime,
        array $exceptReservations = [],
        array $exceptResources = []
    ): array {
        $calculator = new ResourceCapacityCalculator($this);
        $time = $datetime instanceof \Carbon\Carbon ? $datetime : \Carbon\Carbon::parse($datetime);

        return $calculator->calculateCapacityAtTimeArray($time, $exceptReservations, $exceptResources);
    }

    /**
     * Get capacity timeline for a date/time range
     *
     * Returns an array of capacity data points at significant times (reservation starts/ends, range boundaries)
     * Each point contains capacity before/after that time, plus reservation data if applicable.
     *
     * Example usage:
     * $resource = Resource::find("01h2y03by254dm8f3p9nkpfxn9");
     * $capacity = $resource->getCapacityAtDateTimeRange("2023-05-01 00:00:00", "2023-07-10 23:59:59");
     *
     * @param  \Carbon\Carbon|string|int  $from  Start time (Carbon, string, or timestamp in ms)
     * @param  \Carbon\Carbon|string|int  $to  End time (Carbon, string, or timestamp in ms)
     * @param  array<int, string>  $exceptReservations  Reservation IDs to exclude from calculation
     * @param  array<int, string>  $exceptResources  Resource IDs to exclude from calculation
     * @return array<string, array{before: int, after: int, reservation?: array<string, mixed>, start?: bool, end?: bool}>
     *                                                                                                                     Capacity timeline keyed by timestamp (ms), sorted chronologically
     */
    public function getCapacityAtDateTimeRange(
        \Carbon\Carbon|string|int $from,
        \Carbon\Carbon|string|int $to,
        array $exceptReservations = [],
        array $exceptResources = []
    ): array {
        $timeRange = new TimeRange($from, $to);
        $calculator = new ResourceCapacityCalculator($this);

        return $calculator->getCapacityTimeline($timeRange, $exceptReservations, $exceptResources);
    }

    /**
     * Find the lowest available capacity within a capacity timeline
     *
     * @param  array<string, array{after: int}>  $capacityTimeline  Timeline from getCapacityAtDateTimeRange()
     * @return int The minimum available capacity across all time points
     */
    public function lowestCapacityAtDateTimeRange(array $capacityTimeline): int
    {
        $calculator = new ResourceCapacityCalculator($this);

        return $calculator->findLowestCapacity($capacityTimeline);
    }
}
