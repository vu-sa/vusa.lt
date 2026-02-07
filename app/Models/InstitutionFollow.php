<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Represents a user following an institution for notifications.
 *
 * @property string $id
 * @property string $user_id
 * @property string $institution_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Institution $institution
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionFollow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionFollow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstitutionFollow query()
 *
 * @mixin \Eloquent
 */
class InstitutionFollow extends Pivot
{
    use HasFactory, HasUlids;

    protected $table = 'institution_follows';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
