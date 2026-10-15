<?php

namespace App\Http\Requests;

use App\Enums\SystemMaintenanceAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunSystemMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::enum(SystemMaintenanceAction::class)],
        ];
    }

    public function action(): SystemMaintenanceAction
    {
        return SystemMaintenanceAction::from($this->validated('action'));
    }
}
