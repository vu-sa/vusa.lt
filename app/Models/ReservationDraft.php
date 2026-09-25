<?php

namespace App\Models;

use Database\Factories\ReservationDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A user's reservation "cart": the resources they are gathering before submitting a real
 * Reservation. It holds no capacity — availability is recalculated whenever it is read.
 *
 * @property int $id
 * @property string $user_id
 * @property string|null $name
 * @property string|null $description
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ReservationDraftItem> $items
 * @property-read User|null $user
 *
 * @method static \Database\Factories\ReservationDraftFactory factory($count = null, $state = [])
 * @method static Builder<static>|ReservationDraft newModelQuery()
 * @method static Builder<static>|ReservationDraft newQuery()
 * @method static Builder<static>|ReservationDraft query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'description', 'start_time', 'end_time'])]
class ReservationDraft extends Model
{
    /** @use HasFactory<ReservationDraftFactory> */
    use HasFactory, MassPrunable;

    #[\Override]
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ReservationDraftItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReservationDraftItem::class);
    }

    public function hasPeriod(): bool
    {
        return $this->start_time !== null && $this->end_time !== null;
    }

    /**
     * Items cascade in the database, so a mass prune is safe. Item writes touch the draft.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subDays((int) config('vusa.reservation_draft_ttl_days')));
    }
}
