<?php

namespace App\Settings;

use App\Listeners\SendMemberRegistrationNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

class FormSettings extends Settings
{
    public ?string $member_registration_form_id;

    public ?string $member_registration_notification_recipient_role_id;

    public ?string $student_rep_registration_form_id;

    /**
     * Institution type IDs that should show student representative registration.
     * Stored as JSON array.
     *
     * @var array<int>
     */
    public ?array $student_rep_institution_type_ids;

    public static function group(): string
    {
        return 'forms';
    }

    /**
     * Get institution type IDs for student representative registration as a Collection.
     */
    public function getStudentRepInstitutionTypeIds(): Collection
    {
        return collect($this->student_rep_institution_type_ids ?? []);
    }

    /**
     * Set institution type IDs for student representative registration.
     *
     * @param  array<int>  $typeIds
     */
    public function setStudentRepInstitutionTypeIds(array $typeIds): void
    {
        $this->student_rep_institution_type_ids = $typeIds;
    }

    /**
     * Check if a user holds the role configured to receive member registrations.
     *
     * These are the people who actually work the member registration form, so they
     * are also the people allowed to read its registrations.
     *
     * Checks both direct user roles and roles assigned through current duties.
     *
     * @see SendMemberRegistrationNotification which mails the same role
     */
    public function userIsMemberRegistrationRecipient(User $user): bool
    {
        $roleId = $this->member_registration_notification_recipient_role_id;

        if (! $roleId) {
            return false;
        }

        if ($user->roles()->where('id', $roleId)->exists()) {
            return true;
        }

        return $user->current_duties()
            ->whereHas('roles', fn ($query) => $query->where('id', $roleId))
            ->exists();
    }

    /**
     * Tenant ids whose member registrations the user is responsible for.
     *
     * Normally derived from the duties carrying the recipient role. When the role is
     * assigned to the user directly there is no duty to read a tenant from, so all of
     * the user's own tenants are used instead.
     *
     * @return Collection<int, int>
     */
    public function getMemberRegistrationTenantIds(User $user): Collection
    {
        $roleId = $this->member_registration_notification_recipient_role_id;

        if (! $roleId) {
            return collect();
        }

        if ($user->roles()->where('id', $roleId)->exists()) {
            return $user->tenants()->pluck('tenants.id')->unique()->values();
        }

        return $user->current_duties()
            ->whereHas('roles', fn ($query) => $query->where('id', $roleId))
            ->with('institution:id,tenant_id')
            ->get()
            ->map(fn ($duty) => $duty->institution?->tenant_id)
            ->filter()
            ->unique()
            ->values();
    }
}
