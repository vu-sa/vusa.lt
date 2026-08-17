<?php

namespace App\Http\Requests\Approvals;

use App\Enums\ModelEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * The `approvable_type` half shared by every approval endpoint.
 *
 * The type arrives as a ModelEnum backing value (snake_case), not a class name — the controller
 * resolves it through `resolveApprovable()` rather than instantiating whatever it was handed.
 *
 * Authorization stays in the controller: it depends on the resolved approvable, and each endpoint
 * authorizes a different ability against it (`view` for history, the approval flow itself for the
 * mutating ones).
 */
abstract class ApprovableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'approvable_type' => ['required', new Enum(ModelEnum::class)],
        ];
    }
}
