<?php

namespace App\Http\Traits;

use App\Http\Controllers\Admin\ReservationResourceController;
use App\Models\Programme;
use App\Models\Training;

/**
 * Programmes and their days, sections, blocks and parts carry no permissions of
 * their own. Every mutation is authorized against the training that owns the
 * programme, mirroring how {@see ReservationResourceController}
 * authorizes against its parent reservation.
 *
 * A programme with no owning training cannot be authorized against anything, so
 * it is refused rather than allowed through.
 */
trait AuthorizesProgrammes
{
    protected function authorizeProgrammeMutation(?Programme $programme): Training
    {
        $training = $programme?->owningTraining();

        abort_if($training === null, 403, 'This programme is not attached to a training.');

        $this->handleAuthorization('update', $training);

        return $training;
    }

    /**
     * Authorize against the programme an element currently sits in, if it sits in
     * one at all. Guards against pulling an element out of a programme the user
     * cannot edit and into one they can. Elements are always created already
     * attached, so a null programme means an orphan with nothing to protect.
     */
    protected function authorizeCurrentProgrammeIfAttached(?Programme $programme): void
    {
        if ($programme !== null) {
            $this->authorizeProgrammeMutation($programme);
        }
    }
}
