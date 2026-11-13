<?php

namespace App\Services\Learning\SystemSteps;

use Illuminate\Support\Collection;

interface SystemStepDetector
{
    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, string>
     */
    public function doneFor(Collection $userIds): Collection;
}
