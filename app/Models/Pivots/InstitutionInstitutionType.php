<?php

namespace App\Models\Pivots;

use App\Services\InstitutionRelationService;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $institution_id
 * @property int $institution_type_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionInstitutionType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionInstitutionType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionInstitutionType query()
 *
 * @mixin \Eloquent
 */
#[WithoutTimestamps]
class InstitutionInstitutionType extends Pivot
{
    protected static function booted(): void
    {
        static::saved(fn () => InstitutionRelationService::flush());
        static::deleted(fn () => InstitutionRelationService::flush());
    }
}
