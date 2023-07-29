<?php

namespace App\Models\Interfaces;

use App\Services\ModelAuthorizer;

interface Decidable
{
    public function decision($decision, ModelAuthorizer $authorizer);

    public function decisionToProgress();

    public function decisionToApprove();

    public function decisionToReject();

    public function decisionToCancel();
}
