<?php

namespace App\Http\Requests\Cadences;

use App\Models\Cadence;

class UpdateCadenceRequest extends CadenceRequest
{
    #[\Override]
    protected function ignoredCadenceId(): ?string
    {
        $cadence = $this->route('cadence');

        return $cadence instanceof Cadence ? $cadence->id : null;
    }
}
