<?php

namespace App\Http\Requests;

use App\Models\Problem;

class StoreProblemRequest extends ProblemRequest
{
    #[\Override]
    protected string $tenantScopePermission = 'problems.create.padalinys';

    /**
     * Determine if the user is authorized to make this request.
     *
     * `can('create', Problem::class)` is tenant-agnostic, so the `tenant_id` rule inherited
     * from ProblemRequest is what actually confines the problem to a padalinys the user may
     * create in.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Problem::class);
    }
}
