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
 * A direct link: the source institution's members see the target; the target's see the
 * source only when `mutual`. See InstitutionRelationService for the full rule set.
 *
 * @property int $id
 * @property string $source_institution_id
 * @property string $target_institution_id
 * @property InstitutionRelationKind $kind
 * @property bool $mutual
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Institution|null $source
 * @property-read Institution|null $target
 *
 * @method static \Database\Factories\InstitutionLinkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionLink query()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
class InstitutionLink extends Model
{
    use HasFactory, LogsModelActivity;

    #[\Override]
    protected $attributes = [
        'kind' => 'related',
        'mutual' => false,
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'kind' => InstitutionRelationKind::class,
            'mutual' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => InstitutionRelationService::flush());
        static::deleted(fn () => InstitutionRelationService::flush());
    }

    /** @return BelongsTo<Institution, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'source_institution_id');
    }

    /** @return BelongsTo<Institution, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'target_institution_id');
    }
}
