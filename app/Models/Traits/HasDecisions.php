<?php

namespace App\Models\Traits;

use App\Services\ModelAuthorizer;
use App\States\Doing\PendingFinalApproval;
use Illuminate\Support\Str;

trait HasDecisions
{
    private ModelAuthorizer $authorizer;
    private $modelName;

    public function __construct()
    {
        $this->modelName = Str::of(class_basename($this))->camel()->plural();
    }
    
    public function decision($decision, ModelAuthorizer $authorizer)
    {        
        $this->authorizer = $authorizer;

        // based on the decision, call the appropriate method
        $method = 'decisionTo' . Str::ucfirst(Str::camel($decision));

        return $this->$method();
    }

    public function decisionToProgress()
    {
        return $this->state->handleProgress();
    }

    public function decisionToApprove()
    {
        $this->authorizer->forUser(auth()->user())->check($this->modelName . '.update.padalinys');
        
        $this->finalApprovalCheck();
        
        return $this->state->handleApprove();
    }

    public function decisionToReject()
    {
        $this->authorizer->forUser(auth()->user())->check($this->modelName . '.update.padalinys');
        
        $this->finalApprovalCheck();
        
        return $this->state->handleReject();
    }

    public function decisionToCancel()
    {
        return $this->state->handleCancel();
    }

    private function finalApprovalCheck()
    {
        if ($this->state instanceof PendingFinalApproval) {
            abort_if($this->authorizer->isAllScope === false, 403, 'Neturite pakankamų teisių patvirtinti arba atmesti.');
        }
    }
}