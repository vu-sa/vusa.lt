<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MergeUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $keptUser = User::find($this->input('kept_user_id'));
        $sourceIds = (array) $this->input('source_user_ids', []);

        if (! $keptUser) {
            return true;
        }

        if (! $this->user()->can('update', $keptUser)) {
            return false;
        }

        $sources = User::query()->whereIn('id', $sourceIds)->get();

        return $sources->count() !== count($sourceIds)
            || $sources->every(fn (User $source): bool => $this->user()->can('delete', $source));
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('source_user_ids') && $this->filled('merged_user_id')) {
            $this->merge(['source_user_ids' => [$this->input('merged_user_id')]]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kept_user_id' => ['required', 'ulid', SoftDeleteRules::existsLive('users')],
            'merged_user_id' => ['nullable', 'ulid', 'different:kept_user_id'],
            'source_user_ids' => ['required', 'array', 'min:1'],
            'source_user_ids.*' => ['required', 'ulid', SoftDeleteRules::existsLive('users'), 'different:kept_user_id'],
        ];
    }
}
