<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Resource extends Model
{
    use HasFactory, HasUlids, HasTranslations, Searchable, SoftDeletes;

    protected $guarded = [];

    public $translatable = ['name', 'description'];

    // TODO: resource manager method
    // ....

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->using(ReservationResource::class);
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class);
    }

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', 'lt'),
            'description->'.app()->getLocale() => $this->getTranslation('description', 'lt'),
        ];
    }
}
