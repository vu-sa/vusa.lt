<?php

namespace App\Models;

use App\Contracts\GuardsForceDelete;
use App\Contracts\SharepointFileableContract;
use App\Events\FileableNameUpdated;
use App\Models\Pivots\DutyDutyType;
use App\Models\Pivots\DutyTypeRole;
use App\Models\Traits\GuardsForceDeleteWhenReferenced;
use App\Models\Traits\HasSharepointFiles;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\HasTypeHierarchy;
use App\Models\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property-read DutyTypeRole|DutyDutyType|null $pivot
 * @property-read Collection<int, Duty> $duties
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read string|null $force_delete_blocked_reason
 * @property-read bool $has_protocol
 * @property-read bool $has_report
 * @property-read array $translatable_columns_from
 * @property-read DutyType|null $parent
 * @property-read Collection<int, DutyType> $recursiveDescendants
 * @property-read DutyType|null $recursiveParent
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
    }

    /** @return BelongsToMany<Duty, $this, DutyDutyType> */
    public function duties(): BelongsToMany
    {
        return $this->belongsToMany(Duty::class)->using(DutyDutyType::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->using(DutyTypeRole::class);
    }

    public function forceDeleteBlockedReason(): ?string
    {
        return $this->forceDeleteReasonFor([
            'trash.blockers.type_assignments' => $this->duties()->withTrashed()->count(),
            'entities.role.model' => $this->roles()->count() + DB::table('role_can_attach_duty_types')->where('duty_type_id', $this->id)->count(),
            'entities.dutyType.model' => $this->descendants()->withTrashed()->count(),
        ]);
    }
}
