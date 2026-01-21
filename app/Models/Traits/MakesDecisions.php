<?php

namespace App\Models\Traits;

use App\Services\ModelAuthorizer;
use Illuminate\Support\Str;

/**
 * Trait for models that can make decisions.
 *
 * @deprecated This trait is currently unused and may be removed in a future version.
 *
 * @phpstan-ignore trait.unused
 */
trait MakesDecisions
{
    private ModelAuthorizer $authorizer;

    private string $modelName;

    public function decision($decision)
    {
        $this->modelName = Str::of(class_basename($this))->camel()->plural();

        $this->authorizer = app(ModelAuthorizer::class);

        // based on the decision, call the appropriate method
        $method = 'decisionTo'.Str::ucfirst(Str::camel($decision));

        return $this->$method();
    }
}
