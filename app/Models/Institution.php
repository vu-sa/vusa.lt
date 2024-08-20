<?php

namespace App\Models;

use App\Actions\GetInstitutionManagers;
use App\Events\FileableNameUpdated;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Institution extends Model
{
    use HasComments, HasContentRelationships, HasFactory, HasRelationships, HasSharepointFiles, HasTranslations, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $with = ['types'];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    public $translatable = ['name', 'short_name', 'description', 'address'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }

    public function types()
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenants()
    {
        return $this->tenant();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function matters()
    {
        return $this->belongsToMany(Matter::class, 'institutions_matters', 'institution_id', 'matter_id');
    }

    public function meetings()
    {
        return $this->belongsToMany(Meeting::class);
    }

    public function lastMeeting(): ?Meeting
    {
        // get earliest in the future, or if none, latest in past meeting
        return $this->meetings()->where('start_time', '>=', now())->orderBy('start_time', 'asc')->first()
            ?? $this->meetings()->where('start_time', '<', now())->orderBy('start_time', 'desc')->first();
    }

    public function users()
    {
        report('Institution::users() is deprecated. Use Institution::duties()->users() instead.');

        return $this->hasManyDeepFromRelations($this->duties(), (new Duty)->users());
    }

    public function managers()
    {
        return GetInstitutionManagers::execute($this);
    }

    public function related_institution_relationshipables()
    {
        return RelationshipService::getRelatedInstitutionRelations($this);
    }

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', 'lt'),
            'short_name->'.app()->getLocale() => $this->getTranslation('short_name', 'lt'),
        ];
    }

    protected static function booted()
    {
        static::saved(function (Institution $institution) {
            // check if institution name $institution->getChanges()['name'] has changed
            if (array_key_exists('name', $institution->getChanges())) {
                // dispatch event FileableNameUpdated
                FileableNameUpdated::dispatch($institution);
            }
        });
    }
}
