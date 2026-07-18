<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Renaming is open to every collaborator; changing the attached institution
     * changes who has access, so it requires the member-management ability.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        if (! $this->user()->can('update', $workspace)) {
            return false;
        }

        if ($this->exists('institution_id') && $this->input('institution_id') !== $workspace->institution_id) {
            return $this->user()->can('manageMembers', $workspace);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'institution_id' => ['sometimes', 'nullable', 'string', 'exists:institutions,id'],
        ];
    }
}
