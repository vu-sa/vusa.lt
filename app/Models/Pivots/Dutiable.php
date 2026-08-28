<?php

namespace App\Models\Pivots;

use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\Traits\HasTranslations;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $id
 * @property string|null $via_dutiable_id
 * @property string $duty_id
 * @property int|null $tenant_id
 * @property string $dutiable_id
 * @property string $dutiable_type
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string|null $study_program_id
 * @property array|string|null $study_program_note
 * @property string|null $additional_email
 * @property string|null $additional_photo
 * @property string|null $additional_photo_focal_point
 * @property array|string|null $description
 * @property bool $use_original_duty_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Dutiable> $derivedDutiables
 * @property-read Model $dutiable
 * @property-read Duty|null $duty
 * @property-read array $translatable_columns_from
 * @property-read StudyProgram|null $study_program
 * @property-read Tenant|null $tenant
 * @property-read Collection<int, Tenant> $tenants
 * @property-read mixed $translations
 * @property-read User|null $user
 * @property-read Dutiable|null $viaDutiable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable current()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dutiable activeOn(?string $date = null)
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
#[Table(name: 'dutiables')]
class Dutiable extends MorphPivot
{
    // NOTE: for some reason, if Searchable trait is used on this model, it will cause an error
    // in the update route. But only if the queue driver is set to sync.
    use HasFactory, HasRelationships, HasTranslations, HasUlids;

    #[\Override]
    protected $guarded = [];

    #[\Override]
    protected $with = ['study_program'];

    #[\Override]
    protected $dispatchesEvents = [
        'saved' => DutiableChanged::class,
        'deleted' => DutiableChanged::class,
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'use_original_duty_name' => 'boolean',
        ];
    }

    public $translatable = ['description', 'study_program_note'];

    /**
     * `description` is Tiptap `full` preset HTML. It takes precedence over the
     * duty's own description in the public contacts popover
     * (ContactWithPhoto.vue `dutyDescription()`), so it reaches anonymous
     * visitors while any admin who may edit a duty assignment can write it.
     */
    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    /**
     * Ex-officio rows derived from this one have to go before it does.
     *
     * `dutiables.via_dutiable_id` is `nullOnDelete()`, so the database clears the
     * link the instant the parent row disappears — by the time SyncExOfficioDutiables
     * runs its `where('via_dutiable_id', $sourceId)` cleanup there is nothing left to
     * match, and the derived rows survive as permission-granting orphans that look
     * exactly like manual assignments (see AuditExOfficioDutiables). Deleting them
     * here, while the link still exists, is the only ordering that works.
     */
    #[\Override]
    protected static function booted(): void
    {
        static::deleting(function (Dutiable $dutiable): void {
            // Derived rows are leaves — they never grant further ex-officio seats,
            // so this cannot recurse beyond one level.
            if (! is_null($dutiable->via_dutiable_id)) {
                return;
            }

            $dutiable->derivedDutiables()->get()->each->delete();
        });
    }

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

    /**
     * The tenant a cross-tenant representative was assigned for.
     * Null = a regular member belonging to the duty's own tenant.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Rows that have not ended yet, matching what `Duty::current_users()` and every
     * quota check mean by "current" — a future-dated start still counts, because the
     * seat is already allocated.
     */
    public function scopeCurrent($query)
    {
        return $query->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString()));
    }

    /**
     * Rows genuinely in force on the given date. Unlike {@see scopeCurrent()} this also
     * requires the term to have begun, which is what point-in-time questions
     * (who held this seat in March?) need.
     */
    public function scopeActiveOn($query, ?string $date = null)
    {
        $date ??= now()->toDateString();

        return $query->whereDate('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date));
    }

    public function viaDutiable(): BelongsTo
    {
        return $this->belongsTo(Dutiable::class, 'via_dutiable_id');
    }

    public function derivedDutiables(): HasMany
    {
        return $this->hasMany(Dutiable::class, 'via_dutiable_id');
    }

    public function tenants()
    {
        return $this->hasManyDeepFromRelations($this->duty(), (new Duty)->tenants());
    }
}
