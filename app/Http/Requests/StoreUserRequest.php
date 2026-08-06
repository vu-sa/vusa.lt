<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => ['required', 'email', UniqueAmongTrashed::of('users', 'email')],
            'facebook_url' => 'nullable|url',
            'phone' => 'nullable|string',
            'profile_photo_path' => 'nullable|string',
            'profile_photo_focal_point' => 'nullable|string|max:20',
            'pronouns' => ['nullable', 'array'],
            'pronouns.lt' => ['nullable', 'string', 'max:50'],
            'pronouns.en' => ['nullable', 'string', 'max:50'],
            'show_pronouns' => ['nullable', 'boolean'],
            // Only a super admin has these applied (see UserController::store), but they
            // must still be validated: the controller syncs them straight onto the model.
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'current_duties' => ['required', 'array', 'min:1'],
            'current_duties.*' => ['string', SoftDeleteRules::existsLive('duties')],
        ];
    }
}
