<?php

namespace App\Models;

use App\Models\Pivots\Trainable;
use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
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
 * @property Carbon $start_time
 * @property Carbon|null $end_time
 * @property string $organizer_id
 * @property string $institution_id
 * @property string|null $form_id
 * @property int|null $max_participants
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Activity> $activities
 * @property-read Form|null $form
 * @property-read Institution|null $institution
 * @property-read User|null $organizer
 * @property-read Collection<int, Programme> $programmes
 * @property-read Collection<int, TrainingTask> $tasks
 * @property-read Collection<int, Trainable> $trainables
 * @property-read mixed $translations
 * @property-read Collection<int, User> $users
 *
 * @method static \Database\Factories\TrainingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class Training extends Model
{
    use HasFactory, HasRelationships, HasTranslations, HasUlids, LogsActivity, Searchable;

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

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray(): array
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

    public function tenants()
    {
        return $this->tenant();
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
