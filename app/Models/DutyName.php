<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\DutyNameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A name a duty went by until `used_until` (inclusive), kept so old terms read as they did then.
 *
 * @property string $id
 * @property string $duty_id
 * @property array|string|null $name
 * @property Carbon $used_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Duty $duty
 *
 * @method static \Database\Factories\DutyNameFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[Fillable(['duty_id', 'name', 'used_until'])]
class DutyName extends Model
{
    /** @use HasFactory<DutyNameFactory> */
    use HasFactory, HasTranslations, HasUlids;

    public $translatable = ['name'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'used_until' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Duty, $this>
     */
    public function duty(): BelongsTo
    {
        return $this->belongsTo(Duty::class);
    }
}
