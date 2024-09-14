<?php

namespace App\Models\Interfaces;

interface Decidable
{
    public function decision($decision);

    public function decisionToProgress();

    public function decisionToApprove();

    public function decisionToReject();

    public function decisionToCancel();
}
