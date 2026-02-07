<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class BatchUpdateDutyUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $duty = $this->route('duty');

        // Must be able to update the duty
        if (! $this->user()->can('update', $duty)) {
            return false;
        }

        // If creating new users, check users.create.padalinys permission
        $newUsers = $this->input('new_users', []);

        if (! empty($newUsers)) {
            $authorizer = app(ModelAuthorizer::class);

            if (! $authorizer->forUser($this->user())->checkAllRoleables('users.create.padalinys')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_changes' => 'required|array',
            'user_changes.*.user_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Allow temporary IDs for new users (prefixed with 'new-')
                    if (str_starts_with($value, 'new-')) {
                        return;
                    }
                    // Validate existing user IDs exist in the database
                    if (! User::where('id', $value)->exists()) {
                        $fail(__('validation.exists', ['attribute' => 'user_id']));
                    }
                },
            ],
            'user_changes.*.action' => 'required|in:add,remove',
            'user_changes.*.start_date' => 'nullable|date',
            'user_changes.*.end_date' => 'nullable|date',
            'user_changes.*.study_program_id' => 'nullable|string|exists:study_programs,id',
            'new_users' => 'nullable|array',
            'new_users.*.name' => 'required|string|max:255',
            'new_users.*.email' => 'required|email|unique:users,email',
            'new_users.*.phone' => 'nullable|string|max:50',
            'new_users.*.temp_id' => 'required|string', // Track which user_change this belongs to
            'places_to_occupy' => 'nullable|integer|min:1',
        ];
    }
}
