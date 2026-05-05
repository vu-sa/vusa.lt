<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property string $id
 * @property array|string $title
 * @property array|string $description
 * @property array|string|null $solution
 * @property array|string|null $steps_taken
 * @property int $tenant_id
 * @property string $created_by
 * @property string|null $responsible_user_id
 * @property Carbon $occurred_at
 * @property Carbon|null $resolved_at
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, ProblemCategory> $categories
 * @property-read User|null $createdBy
 * @property-read Collection<int, Institution> $institutions
 * @property-read User|null $responsibleUser
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Problem withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Problem extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsActivity, Searchable, SoftDeletes;

    protected $guarded = [];

    public $translatable = ['title', 'description', 'solution', 'steps_taken'];

    protected $casts = [
        'occurred_at' => 'date',
        'resolved_at' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty();
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'solution' => $this->getTranslations('solution'),
            'steps_taken' => $this->getTranslations('steps_taken'),
            'status' => $this->status,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProblemCategory::class);
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved' && ! is_null($this->resolved_at);
    }

    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }
}
