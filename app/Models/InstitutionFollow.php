<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Represents a user following an institution for notifications.
 *
 * @property string $id
 * @property string $user_id
 * @property string $institution_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Institution|null $institution
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionFollow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionFollow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionFollow query()
 *
 * @mixin \Eloquent
 */
#[Table(name: 'institution_follows', keyType: 'string')]
#[WithoutIncrementing]
#[Unguarded]
class InstitutionFollow extends Pivot
{
    use HasFactory, HasUlids;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
