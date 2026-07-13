<?php

namespace App\Events;

use App\Models\Pivots\Dutiable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched whenever a dutiable pivot row is saved or deleted.
 *
 * The event deliberately carries scalars rather than the Dutiable model. It is
 * dispatched on `deleted` as well as `saved`, and its listeners are queued — so a
 * model payload (via SerializesModels) would be re-fetched from a row that no
 * longer exists, throwing ModelNotFoundException before the listener ever ran.
 */
class DutiableChanged
{
    use Dispatchable;

    /** The pivot row's own id. */
    public readonly string $dutiableRowId;

    public readonly string $dutyId;

    /** The morph type of the holder: User, Contact, etc. */
    public readonly string $dutiableType;

    /** The holder's id — `dutiable_id` on the pivot. */
    public readonly string $modelId;

    public readonly ?string $viaDutiableId;

    public readonly bool $wasDeleted;

    /**
     * Note the model-typed parameter: Eloquent's `$dispatchesEvents` always
     * constructs the event with exactly one argument, the model itself. Only
     * attributes are read here — no relation walks — because this runs inside
     * AccessChangeAnalyzer's open transaction on every save and delete.
     */
    public function __construct(Dutiable $dutiable)
    {
        $this->dutiableRowId = (string) $dutiable->id;
        $this->dutyId = $dutiable->duty_id;
        $this->dutiableType = $dutiable->dutiable_type;
        $this->modelId = (string) $dutiable->dutiable_id;
        $this->viaDutiableId = $dutiable->via_dutiable_id;

        // Eloquent clears `exists` in performDeleteOnModel() *before* firing the
        // `deleted` event, so this is the delete signal. It has to be captured now:
        // by the time a queued listener runs, the row is gone.
        $this->wasDeleted = ! $dutiable->exists;
    }
}
