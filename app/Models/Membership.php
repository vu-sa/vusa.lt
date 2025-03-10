<?php

namespace App\Models;

use App\Models\Pivots\MembershipUser;
use App\Models\Pivots\Trainable;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Membership extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipFactory> */
    use HasFactory, HasTranslations, HasUlids, Searchable;

    public $translatable = [
        'name',
    ];

    protected $fillable = [
        'name',
        'tenant_id',
    ];

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->using(MembershipUser::class)->withTimestamps()->withPivot('start_date', 'end_date');
    }

    public function availableTrainings()
    {
        return $this->morphToMany(Training::class, 'trainable')->using(Trainable::class);
    }
}
