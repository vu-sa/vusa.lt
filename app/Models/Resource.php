<?php

namespace App\Models;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Actions\GetResourceManagers;
use App\Models\Pivots\ReservationResource;
use App\Models\Traits\HasTranslations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', 'lt'),
            'description->'.app()->getLocale() => $this->getTranslation('description', 'lt'),
        ];
    }

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->using(ReservationResource::class)->withPivot(['state', 'start_time', 'end_time', 'quantity']);
    }

    public function active_reservations()
    {
        return $this->reservations()->wherePivotIn('state', ['created', 'reserved', 'lent']);
    }

    public function managers()
    {
        return GetResourceManagers::execute($this);
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class);
    }

    public function leftCapacityAtTime($datetime, $symbol_start = '<', $symbol_end = '>=')
    {
        // where pivot state is reserved or lent
        return $this->capacity - $this->active_reservations()
            ->wherePivot('start_time', $symbol_start, $datetime)->wherePivot('end_time', $symbol_end, $datetime)->sum('quantity');
    }

    public function leftCapacityAtTimeArray($datetime): array
    {
        // where pivot state is reserved or lent
        return [
            'before' => $this->leftCapacityAtTime($datetime, '<', '>='),
            'after' => $this->leftCapacityAtTime($datetime, '<=', '>')];
    }

    // returns array of left capacity at each intersection of reservation resource start and end time

    // $resource = Resource::find("01h2y03by254dm8f3p9nkpfxn9");
    // $resource->leftCapacityAtTimePeriod("2023-05-01 00:00:00", "2023-07-10 23:59:59");
    public function getCapacityAtDateTimeRange($from, $to): array
    {

        // if $from and $to are numbers (timestamps), convert them to Carbon
        if (is_numeric($from)) {
            $from = Carbon::createFromTimestampMs($from);
        }

        if (is_numeric($to)) {
            $to = Carbon::createFromTimestampMs($to);
        }

        $leftCapacity = [];
        $reservations = $this->active_reservations()->wherePivot('start_time', '<=', $to)->wherePivot('end_time', '>=', $from)->get();

        // get left capacity at start and end of each reservation
        $reservations->each(function ($reservation) use (&$leftCapacity, $from, $to) {
            $start = Carbon::parse($reservation->pivot->start_time) > Carbon::parse($from) ? $reservation->pivot->start_time : $from;
            $end = Carbon::parse($reservation->pivot->end_time) < Carbon::parse($to) ? $reservation->pivot->end_time : $to;

            $leftCapacity[strval(Carbon::parse($start)->getTimestampMs())] = $this->leftCapacityAtTimeArray($start)
                + ['reservation' => $reservation->toArray(), 'start' => true];
            $leftCapacity[strval(Carbon::parse($end)->getTimestampMs())] = $this->leftCapacityAtTimeArray($end)
                + ['reservation' => $reservation->toArray(), 'end' => true];
        });

        // get left capacity at start and end of time period
        $leftCapacity[strval(Carbon::parse($from)->getTimestampMs())] = $this->leftCapacityAtTimeArray($from);
        $leftCapacity[strval(Carbon::parse($to)->getTimestampMs())] = $this->leftCapacityAtTimeArray($to);

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
