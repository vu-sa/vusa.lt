<?php

namespace App\Models;

use Database\Factories\ReservationDraftItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reservation_draft_id
 * @property string $resource_id
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ReservationDraft $draft
 * @property-read \App\Models\Resource|null $resource
 */
#[Fillable(['resource_id', 'quantity'])]
class ReservationDraftItem extends Model
{
    /** @use HasFactory<ReservationDraftItemFactory> */
    use HasFactory;

    /** Keeps the draft's updated_at fresh so pruning measures the last real change. */
    protected $touches = ['draft'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<ReservationDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(ReservationDraft::class, 'reservation_draft_id');
    }

    /**
     * Trashed resources stay visible so the cart can say the item is gone instead of dropping it.
     *
     * @return BelongsTo<\App\Models\Resource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class)->withTrashed();
    }
}
