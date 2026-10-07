<?php

namespace App\Models;

use App\Enums\InstitutionRelationKind;
use App\Models\Traits\LogsModelActivity;
use App\Services\InstitutionRelationService;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Links every institution of the source type to every institution of the target type in the
 * same tenant — or, when `cross_tenant`, sources in pagrindinis to targets in every padalinys.
 *
 * Source and target may be the same type: that relates same-type institutions to each other.
 *
 * @property int $id
 * @property int $source_type_id
 * @property int $target_type_id
 * @property InstitutionRelationKind $kind
 * @property bool $mutual
 * @property bool $cross_tenant
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read InstitutionType|null $source
 * @property-read InstitutionType|null $target
 *
 * @method static \Database\Factories\InstitutionTypeLinkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionTypeLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionTypeLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionTypeLink query()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
class InstitutionTypeLink extends Model
{
    use HasFactory, LogsModelActivity;

    #[\Override]
    protected $attributes = [
        'kind' => 'related',
        'mutual' => false,
        'cross_tenant' => false,
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'kind' => InstitutionRelationKind::class,
            'mutual' => 'boolean',
            'cross_tenant' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => InstitutionRelationService::flush());
        static::deleted(fn () => InstitutionRelationService::flush());
    }

    /** @return BelongsTo<InstitutionType, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class, 'source_type_id');
    }

    /** @return BelongsTo<InstitutionType, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class, 'target_type_id');
    }
}
