<?php

namespace App\Models;

use App\Actions\GetInstitutionManagers;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\Trainable;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasContentRelationships;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property array|string|null $name
 * @property array|string|null $short_name
 * @property string $alias
 * @property array|string|null $description
 * @property array|string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $image_url
 * @property string|null $logo_url
 * @property string|null $facebook_url
 * @property string|null $instagram_url
 * @property int|null $tenant_id
 * @property int $is_active
 * @property string $contacts_layout
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \App\Models\Pivots\Relationshipable|Trainable|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Training> $availableTrainings
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Document> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Duty> $duties
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SharepointFile> $files
 * @property-read mixed $maybe_short_name
 * @property-read mixed $related_institutions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relationship> $incomingRelationships
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relationship> $outgoingRelationships
 * @property-read \App\Models\Tenant|null $tenant
 * @property-read mixed $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Type> $types
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\InstitutionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Institution withoutTrashed()
 * @mixin \Eloquent
 */
class Institution extends Model
{
    use HasComments, HasContentRelationships, HasFactory, HasRelationships, HasSharepointFiles, HasTranslations, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $with = ['types'];

    public $translatable = ['name', 'short_name', 'description', 'address'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }

    public function types(): MorphToMany
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class);
    }

    public function lastMeeting(): ?Meeting
    {
        // get earliest in the future, or if none, latest in past meeting
        return $this->meetings()->where('start_time', '>=', now())->orderBy('start_time', 'asc')->first()
            ?? $this->meetings()->where('start_time', '<', now())->orderBy('start_time', 'desc')->first();
    }

    public function users(): HasManyDeep
    {
        /* report('Institution::users() is deprecated. Use Institution::duties()->users() instead.'); */

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

    public function getRelatedInstitutionsAttribute()
    {
        return RelationshipService::getRelatedInstitutions($this);
    }

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
            'short_name->'.app()->getLocale() => $this->getTranslation('short_name', app()->getLocale()),
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

    public function getMaybeShortNameAttribute()
    {
        return $this->short_name ?? $this->name;
    }

    public function availableTrainings()
    {
        return $this->morphToMany(Training::class, 'trainable')->using(Trainable::class);
    }
}
