<?php

namespace App\States\Doing;

class Completed extends DoingState
{
    public static $name = 'completed';

    public function color(): string
    {
        return 'green';
    }

    public function handleProgress(): void
    {
        abort(403, 'Veikla jau yra užbaigta.');
    }

    public function handleApprove(): void
    {
        abort(403, 'Negalima patvirtinti jau užbaigtos veiklos.');
    }

    public function handleReject(): void
    {
        abort(403, 'Negalima atmesti jau užbaigtos veiklos.');
    }

    public function handleCancel(): void
    {
        abort(403, 'Negalima atšaukti jau užbaigtos veiklos.');
    }
}
