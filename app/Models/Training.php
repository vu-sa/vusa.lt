<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Models\Pivots\Trainable;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
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
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Form|null $form
 * @property-read string|null $force_delete_blocked_reason
 * @property-read array $translatable_columns_from
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'description',
    'institution_id',
    'start_time',
    'address',
    'end_time',
    'meeting_url',
    'image',
    'max_participants',
])]
class Training extends Model implements GuardsForceDelete
{
    use GuardsForceDeleteWhenReferenced, HasFactory, HasRelationships, HasTranslations, HasUlids, LogsModelActivity, Searchable, SoftDeletes;

    #[\Override]
    public $table = 'trainings';

    public $translatable = ['name', 'description'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
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

    /**
     * `training_user.training_id` restricts deletes: who attended a training is a
     * record in its own right.
     */
    public function forceDeleteBlockedReason(): ?string
    {
        return $this->forceDeleteReasonFor([
            'trash.blockers.training_participation' => $this->countedRelation('users'),
        ]);
    }
}
