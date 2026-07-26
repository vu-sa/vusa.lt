<?php

namespace App\Contracts;

use App\Http\Traits\AuthorizesProgrammes;
use App\Models\Programme;

/**
 * A programme element (day, section, block or part) that can name the programme
 * it belongs to.
 *
 * Programme elements carry no permissions of their own — authorization walks up
 * to the programme and from there to the training that owns it. See
 * {@see AuthorizesProgrammes}.
 */
interface BelongsToProgramme
{
    /**
     * The programme this element belongs to, or null when it is not attached to
     * one (a newly built or orphaned element).
     */
    public function owningProgramme(): ?Programme;
}
