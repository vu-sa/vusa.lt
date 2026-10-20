<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Contracts\SharepointFileableContract;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\DutyDutyType;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasTypeHierarchy;
use App\Models\Traits\LogsModelActivity;
use App\Services\SystemDutyTypes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property array|string|null $title
 * @property array|string|null $description
 * @property string|null $slug
 * @property array<array-key, mixed>|null $extra_attributes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Collection<int, DutyType> $descendants
 * @property-read DutyDutyType|null $pivot
 * @property-read Collection<int, Duty> $duties
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read string|null $force_delete_blocked_reason
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read array $translatable_columns_from
 * @property-read DutyType|null $parent
 * @property-read Collection<int, DutyType> $recursiveDescendants
 * @property-read DutyType|null $recursiveParent
 * @property-read Collection<int, ResponsibilityGrant> $responsibilityGrants
 * @property-read Collection<int, Role> $roles
 * @property-read mixed $translations
 *
 * @method static \Database\Factories\DutyTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType whereTranslationLike(array|string $columns, string $search, ?array $locales = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyType withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['title', 'description', 'slug', 'parent_id', 'extra_attributes'])]
class DutyType extends Model implements GuardsForceDelete, SharepointFileableContract
{
    use GuardsForceDeleteWhenReferenced, HasFactory, HasSharepointFiles, HasTranslations, HasTypeHierarchy, LogsModelActivity, SoftDeletes;

    protected $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return ['extra_attributes' => 'array'];
    }

    protected function sanitizedHtmlTranslations(): array
    {
        return ['description'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $type): void {
            if ($type->isDirty('title')) {
                FileableNameUpdated::dispatch($type);
            }
        });

        // The contact pages fall back to slugs and skip trashed types, so any of these can move them.
        static::saved(fn () => app(SystemDutyTypes::class)->flush());
        static::deleted(fn () => app(SystemDutyTypes::class)->flush());
        static::restored(fn () => app(SystemDutyTypes::class)->flush());
    }

    /** @return BelongsToMany<Duty, $this, DutyDutyType> */
    public function duties(): BelongsToMany
    {
        return $this->belongsToMany(Duty::class)->using(DutyDutyType::class);
    }

    /**
     * Responsibilities every duty of this type receives.
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'grantee', 'responsibility_grants', 'grantee_id', 'role_id');
    }

    /** @return MorphMany<ResponsibilityGrant, $this> */
    public function responsibilityGrants(): MorphMany
    {
        return $this->morphMany(ResponsibilityGrant::class, 'grantee');
    }

    /**
     * Why the type must stay: a public contact page groups by it.
     */
    public function deleteBlockedReason(): ?string
    {
        $parts = app(SystemDutyTypes::class)->partsOf($this);

        return $parts === [] ? null : (string) __('settings.purposes.duty_types.delete_blocked', [
            'parts' => implode(', ', array_map(fn ($part): string => (string) __($part->labelKey()), $parts)),
        ]);
    }

    public function forceDeleteBlockedReason(): ?string
    {
        return $this->deleteBlockedReason() ?? $this->forceDeleteReasonFor([
            'trash.blockers.type_assignments' => $this->duties()->withTrashed()->count(),
            'entities.role.model' => $this->roles()->count() + DB::table('role_can_attach_duty_types')->where('duty_type_id', $this->id)->count(),
            'entities.dutyType.model' => $this->descendants()->withTrashed()->count(),
        ]);
    }
}
