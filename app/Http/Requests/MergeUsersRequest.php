<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\SoftDeleteRules;
use App\Support\UserMergeGuard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MergeUsersRequest extends FormRequest
{
    private ?User $keptUser = null;

    /** @var Collection<int, User>|null */
    private ?Collection $sourceUsers = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $keptUser = $this->keptUser();

        if (! $keptUser) {
            return true;
        }

        if (! $this->user()->can('update', $keptUser)) {
            return false;
        }

        $sources = $this->sourceUsers();

        return $sources->count() !== count((array) $this->input('source_user_ids', []))
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
            'source_user_ids.*' => ['required', 'ulid', 'distinct', SoftDeleteRules::existsLive('users'), 'different:kept_user_id'],
            // Only one address can stay the login, so the admin picks it among the merged records' own.
            'login_email' => ['required', 'email', Rule::in($this->involvedEmails())],
            'redirect_to_kept' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $keptUser = $this->keptUser();

                if (! $keptUser) {
                    return;
                }

                foreach ($this->sourceUsers()->values() as $index => $source) {
                    $reason = UserMergeGuard::blockedReason($keptUser, $source);

                    // Moving a direct role along would grant access nobody reviewed.
                    if ($reason === null && ($source->roles()->exists() || $source->permissions()->exists())) {
                        $reason = __('users.merge.direct_roles', ['name' => $source->name]);
                    }

                    if ($reason !== null) {
                        $validator->errors()->add("source_user_ids.{$index}", $reason);
                    }
                }
            },
        ];
    }

    public function keptUser(): ?User
    {
        $id = $this->input('kept_user_id');

        return $this->keptUser ??= is_string($id) ? User::query()->find($id) : null;
    }

    /** @return Collection<int, User> */
    public function sourceUsers(): Collection
    {
        $ids = array_filter((array) $this->input('source_user_ids', []), is_string(...));

        return $this->sourceUsers ??= User::query()->whereIn('id', $ids)->get();
    }

    /** @return list<string> */
    private function involvedEmails(): array
    {
        return collect([$this->keptUser()])
            ->concat($this->sourceUsers())
            ->filter()
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }
}
