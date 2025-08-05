<?php

namespace App\Models;

use App\Models\Pivots\Trainable;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasUnitRelation;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property array|string $name
 * @property array|string $description
 * @property string|null $address
 * @property string|null $meeting_url
 * @property string|null $image
 * @property string $status
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property string $organizer_id
 * @property string $institution_id
 * @property string|null $form_id
 * @property int|null $max_participants
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \App\Models\Form|null $form
 * @property-read \App\Models\Institution $institution
 * @property-read \App\Models\User $organizer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Programme> $programmes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingTask> $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Trainable> $trainables
 * @property-read mixed $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @method static \Database\Factories\TrainingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocales(string $column, array $locales)
 * @mixin \Eloquent
 */
class Training extends Model
{
    use HasFactory, HasRelationships, HasTranslations, HasUlids, HasUnitRelation, LogsActivity, Searchable;

    public $table = 'trainings';

    protected $fillable = [
        'name',
        'description',
        'institution_id',
        'start_time',
        'address',
        'end_time',
        'meeting_url',
        'image',
        'max_participants',
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
        ];
    }

    public function trainables()
    {
        return $this->hasMany(Trainable::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function tenant()
    {
        return $this->hasOneDeepFromRelations($this->institution(), (new Institution)->tenant());
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function tasks()
    {
        return $this->hasMany(TrainingTask::class);
    }

    public function programmes()
    {
        return $this->morphToMany(Programme::class, 'programmable');
    }
}
