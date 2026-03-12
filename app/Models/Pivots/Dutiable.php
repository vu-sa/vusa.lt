<?php

namespace App\Models\Pivots;

use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\Traits\HasTranslations;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string $duty_id
 * @property string $dutiable_id
 * @property string $dutiable_type
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string|null $study_program_id
 * @property string|null $additional_email
 * @property string|null $additional_photo
 * @property array|string|null $description
 * @property bool $use_original_duty_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Model $dutiable
 * @property-read Duty $duty
 * @property-read StudyProgram|null $study_program
 * @property-read Collection<int, Tenant> $tenants
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
    use HasFactory, HasRelationships, HasTranslations, HasUlids;

    protected $table = 'dutiables';

    protected $guarded = [];

    protected $with = ['study_program'];

    protected $dispatchesEvents = [
        'saved' => DutiableChanged::class,
        'deleted' => DutiableChanged::class,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'use_original_duty_name' => 'boolean',
        ];
    }

    public $translatable = ['description'];

    /**
     * @return MorphTo<Model, $this>
     */
    public function dutiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Duty, $this>
     */
    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function study_program(): BelongsTo
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
