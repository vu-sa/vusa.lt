<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A closed day for one padalinys: the institutions per activity status as the dashboard would have
 * shown them that day, and what changed (`*_registered` and `*_attention` are that day's activity).
 *
 * @property int $id
 * @property Carbon $taken_on
 * @property int $tenant_id
 * @property int $all
 * @property int $needs_attention
 * @property int $overdue
 * @property int $approaching
 * @property int $no_activity
 * @property int $recent_check_in
 * @property int $current
 * @property int $meetings_held
 * @property int $meetings_registered
 * @property int $check_ins_registered
 * @property int $entered_attention
 * @property int $left_attention
 * @property bool $is_reconstructed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property array<array-key, mixed>|null $institution_statuses
 * @property array<array-key, mixed>|null $meeting_ids
 * @property int $population_added
 * @property int $population_removed
 * @property-read Tenant $tenant
 *
 * @method static \Database\Factories\InstitutionStatusSnapshotFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionStatusSnapshot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionStatusSnapshot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionStatusSnapshot query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'taken_on',
    'tenant_id',
    'all',
    'needs_attention',
    'overdue',
    'approaching',
    'no_activity',
    'recent_check_in',
    'current',
    'meetings_held',
    'meetings_registered',
    'check_ins_registered',
    'entered_attention',
    'left_attention',
    'is_reconstructed',
    'institution_statuses',
    'meeting_ids',
    'population_added',
    'population_removed',
])]
class InstitutionStatusSnapshot extends Model
{
    use HasFactory;

    /** The counts that add up across padaliniai. */
    public const array SUMMED_COLUMNS = [
        'all', 'needs_attention', 'overdue', 'approaching', 'no_activity', 'recent_check_in', 'current',
        'meetings_held', 'meetings_registered', 'check_ins_registered', 'entered_attention', 'left_attention',
        'population_added', 'population_removed',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'taken_on' => 'date:Y-m-d',
            'is_reconstructed' => 'boolean',
            'institution_statuses' => 'array',
            'meeting_ids' => 'array',
        ];
    }
}
