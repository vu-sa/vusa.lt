<?php

namespace App\Models\Traits;

use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

trait MakesDecisions
{
    private ModelAuthorizer $authorizer;

    private string $modelName;

    public function decision($decision, ModelAuthorizer $authorizer)
    {
        $this->modelName = Str::of(class_basename($this))->camel()->plural();

        $this->authorizer = $authorizer;

        // based on the decision, call the appropriate method
        $method = 'decisionTo'.Str::ucfirst(Str::camel($decision));

        return $this->$method();
    }
}
