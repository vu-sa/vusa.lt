<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use App\Policies\ProblemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Support\LogOptions;

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
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Collection<int, ProblemCategory> $categories
 * @property-read User|null $createdBy
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, Institution> $institutions
 * @property-read User|null $responsibleUser
 * @property-read Tenant $tenant
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\ProblemFactory factory($count = null, $state = [])
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
#[Unguarded]
class Problem extends Model
{
    use HasFactory, HasTranslations, HasUlids, LogsModelActivity, Searchable, SoftDeletes;

    public $translatable = ['title', 'description', 'solution', 'steps_taken'];

    /**
     * The Tiptap-authored fields rendered with `v-html` on ShowProblem. Problems
     * are visible to every authenticated user (see {@see ProblemPolicy::view()})
     * while only tenant staff may write them, so unsanitized markup here would let
     * one tenant's editor reach every other user's browser.
     */
    protected function sanitizedHtmlTranslations(): array
    {
        return ['description', 'solution', 'steps_taken'];
    }

    /**
     * Log the raw {"lt":"…","en":"…"} JSON for the diffable rich fields
     * instead of the trait default, which resolves getAttribute() -- for a
     * translatable attribute that returns only the saving admin's session
     * locale (see HasTranslations::getAttributeValue()). Left as single-locale
     * strings, an EN-only edit made under an LT session would leave the LT
     * string unchanged, and dontLogEmptyChanges() would silently drop the
     * whole activity. Only the sanitized rich fields, not every translatable
     * attribute (title stays single-locale; it isn't diffed).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions()
            ->useAttributeRawValues($this->sanitizedHtmlTranslations());
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

    protected function casts(): array
    {
        return [
            'occurred_at' => 'date',
            'resolved_at' => 'date',
        ];
    }
}
