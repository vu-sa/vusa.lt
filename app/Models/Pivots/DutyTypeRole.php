<?php

namespace App\Models\Pivots;

use App\Models\DutyType;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Transitional(responsibility-grants): read by the grants backfill only; remove with the legacy table.
 *
 * @property string $role_id
 * @property int $duty_type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role $role
 * @property-read DutyType|null $type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyTypeRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyTypeRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DutyTypeRole query()
 *
 * @mixin \Eloquent
 */
class DutyTypeRole extends Pivot
{
    public function type(): BelongsTo
    {
        return $this->belongsTo(DutyType::class, 'duty_type_id')->withTrashed();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
