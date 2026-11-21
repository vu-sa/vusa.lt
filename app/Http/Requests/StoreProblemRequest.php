<?php

namespace App\Http\Requests;

class StoreProblemRequest extends ProblemRequest
{
    #[\Override]
    protected string $tenantScopePermission = 'problems.create.padalinys';
}
