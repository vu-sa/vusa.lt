<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', UniqueAmongTrashed::of('users', 'email')->ignore($user->id)],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'profile_photo_path' => ['nullable', 'string', 'max:255'],
            'profile_photo_focal_point' => ['nullable', 'string', 'max:20'],
            'pronouns' => ['nullable', 'array'],
            'pronouns.lt' => ['nullable', 'string', 'max:50'],
            'pronouns.en' => ['nullable', 'string', 'max:50'],
            'show_pronouns' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'current_duties' => ['nullable', 'array'],
            'current_duties.*' => [SoftDeleteRules::existsLive('duties')],
        ];
    }

    /**
     * Refuse changes to the identity fields (name, email) when the actor lacks the
     * `updateIdentity` ability.
     *
     * Email is the login identity — AuthController::callback resolves the Microsoft
     * account by users.email — so an unchecked change is an account takeover.
     *
     * The form always posts `name` and `email` whether or not they were touched, so
     * this must compare against the stored values and only complain about an actual
     * change. A blanket `prohibited` rule would 422 every save a tenant admin makes
     * on a user who also holds duties elsewhere.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $validator): void {
            /** @var User $target */
            $target = $this->route('user');

            $changed = collect(['name', 'email'])->filter(
                fn (string $field) => $this->has($field)
                    && trim((string) $this->input($field)) !== (string) $target->getAttribute($field)
            );

            if ($changed->isEmpty() || $this->user()->can('updateIdentity', $target)) {
                return;
            }

            $blocking = app(UserPolicy::class)->blockingTenantNames($this->user(), $target);

            $message = $blocking->isNotEmpty()
                ? __('users.identity_locked_tenants', ['tenants' => $blocking->join(', ')])
                : __('users.identity_locked_protected');

            $changed->each(fn (string $field) => $validator->errors()->add($field, $message));
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.name')]),
            'email.required' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
            'email.email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('validation.attributes.email')]),
        ];
    }
}
