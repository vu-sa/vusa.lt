<?php

namespace App\Models\Pivots;

use App\Services\RelationshipService;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[WithoutTimestamps]
class InstitutionInstitutionType extends Pivot
{
    protected static function booted(): void
    {
        $invalidate = fn (self $assignment) => RelationshipService::clearRelatedInstitutionsCache($assignment->institution_id);

        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
