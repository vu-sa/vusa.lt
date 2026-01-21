<?php

namespace App\Models\Pivots;

use App\Models\Duty;
use App\Models\StudyProgram;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasUnitRelation;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $duty_id
 * @property string $dutiable_id
 * @property string $dutiable_type
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $study_program_id
 * @property string|null $additional_email
 * @property string|null $additional_photo
 * @property array|string|null $description
 * @property bool $use_original_duty_name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model $dutiable
 * @property-read Duty $duty
 * @property-read StudyProgram|null $study_program
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $tenants
 * @property-read mixed $translations
 * @property-read User|null $user
 *
 * @method static \Database\Factories\Pivots\DutiableFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable whereLocales(string $column, array $locales)
 *
 * @mixin \Eloquent
 */
class Dutiable extends MorphPivot
{
    // NOTE: for some reason, if Searchable trait is used on this model, it will cause an error
    // in the update route. But only if the queue driver is set to sync.
    use HasFactory, HasRelationships, HasTranslations, HasUlids, HasUnitRelation;

    protected $table = 'dutiables';

    protected $guarded = [];

    protected $with = ['study_program'];

    protected $dispatchesEvents = [
        'saved' => \App\Events\DutiableChanged::class,
        'deleted' => \App\Events\DutiableChanged::class,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'use_original_duty_name' => 'boolean',
        ];
    }

    public $translatable = ['description'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function dutiable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Duty, $this>
     */
    public function duty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Duty::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<StudyProgram, $this>
     */
    public function study_program(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'dutiable_id');
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->duty(), (new Duty)->tenants());
    }
}
