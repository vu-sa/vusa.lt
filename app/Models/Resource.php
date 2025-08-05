<?php

namespace App\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Actions\GetResourceManagers;
use App\Models\Pivots\ReservationResource;
use App\Models\Traits\HasTranslations;
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
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed>|null $description
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
 * @mixin \Eloquent
 */
class Resource extends Model implements HasMedia
{
    use EagerLoadPivotTrait, HasFactory, HasTranslations, HasUlids, InteractsWithMedia, Searchable, SoftDeletes;

    protected $guarded = [];

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

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class)->using(ReservationResource::class)->withPivot(['state', 'start_time', 'end_time', 'quantity']);
    }

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

    public function leftCapacityAtTime(\Carbon\Carbon|string $datetime, string $symbol_start = '<', string $symbol_end = '>=', array $exceptReservations = [], array $exceptResources = []): int
    {
        // where pivot state is reserved or lent
        $builder = $this->active_reservations()
            ->wherePivot('start_time', $symbol_start, $datetime)->wherePivot('end_time', $symbol_end, $datetime);

        if (! empty($exceptReservations) && in_array($this->id, $exceptResources)) {
            $builder = $builder->whereNotIn('reservations.id', $exceptReservations);
        }

        return $this->capacity - $builder->sum('quantity');
    }

    public function leftCapacityAtTimeArray($datetime, $exceptReservations = [], $exceptResources = []): array
    {
        // where pivot state is reserved or lent
        return [
            'before' => $this->leftCapacityAtTime($datetime, '<', '>=', $exceptReservations, $exceptResources),
            'after' => $this->leftCapacityAtTime($datetime, '<=', '>', $exceptReservations, $exceptResources),
        ];
    }

    // returns array of left capacity at each intersection of reservation resource start and end time

    // $resource = Resource::find("01h2y03by254dm8f3p9nkpfxn9");
    // $resource->leftCapacityAtTimePeriod("2023-05-01 00:00:00", "2023-07-10 23:59:59");
    public function getCapacityAtDateTimeRange(\Carbon\Carbon|string|int $from, \Carbon\Carbon|string|int $to, array $exceptReservations = [], array $exceptResources = []): array
    {
        /* dd($exceptReservations, $exceptResources); */
        // if $from and $to are numbers (timestamps), convert them to Carbon
        if (is_numeric($from)) {
            $from = Carbon::createFromTimestampMs($from);
        }

        if (is_numeric($to)) {
            $to = Carbon::createFromTimestampMs($to);
        }

        $leftCapacity = [];
        $reservationBuilder = $this->active_reservations()->wherePivot('start_time', '<=', $to)->wherePivot('end_time', '>=', $from);

        // For reservationResource update routes in ShowReservation, discard amount of resource that is already reserved in the calculation
        // Also check if $this->id is in $exceptResources
        if (! empty($exceptReservations) && in_array($this->id, $exceptResources)) {
            $reservationBuilder = $reservationBuilder->whereNotIn('reservations.id', $exceptReservations);
            $reservations = $reservationBuilder->get();
        } else {
            $reservations = $reservationBuilder->get();
        }

        // get left capacity at start and end of each reservation
        $reservations->each(function ($reservation) use (&$leftCapacity, $from, $to) {
            /** @phpstan-ignore-next-line */
            $start = Carbon::parse($reservation->pivot->start_time) > Carbon::parse($from) ? $reservation->pivot->start_time : $from;
            /** @phpstan-ignore-next-line */
            $end = Carbon::parse($reservation->pivot->end_time) < Carbon::parse($to) ? $reservation->pivot->end_time : $to;

            $leftCapacity[strval(Carbon::parse($start)->getTimestampMs())] = $this->leftCapacityAtTimeArray($start)
                + ['reservation' => $reservation->toArray(), 'start' => true];
            $leftCapacity[strval(Carbon::parse($end)->getTimestampMs())] = $this->leftCapacityAtTimeArray($end)
                + ['reservation' => $reservation->toArray(), 'end' => true];
        });

        // get left capacity at start and end of time period
        $leftCapacity[strval(Carbon::parse($from)->getTimestampMs())] = $this->leftCapacityAtTimeArray(datetime: $from, exceptReservations: $exceptReservations, exceptResources: $exceptResources);
        $leftCapacity[strval(Carbon::parse($to)->getTimestampMs())] = $this->leftCapacityAtTimeArray(datetime: $to, exceptReservations: $exceptReservations, exceptResources: $exceptResources);

        ksort($leftCapacity);

        return $leftCapacity;
    }

    public function lowestCapacityAtDateTimeRange(array $leftCapacity): int
    {
        $lowestCapacity = $this->capacity;
        foreach ($leftCapacity as $capacity) {
            if ($capacity['after'] < $lowestCapacity) {
                $lowestCapacity = $capacity['after'];
            }
        }

        return $lowestCapacity;
    }
}
