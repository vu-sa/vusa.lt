<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\InstitutionSecretary;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which notification types a user can currently receive, so Pranešimų nustatymai lists only those.
 * Mirrors the audience resolvers (GetMeetingOverseers, GetResourceManagers, the registration
 * listeners) from the user's side; super admins see every type.
 */
class NotificationAudience
{
    /**
     * @return array<int, NotificationType>
     */
    public function typesFor(User $user): array
    {
        return array_values(array_filter(
            NotificationType::configurable(),
            fn (NotificationType $type): bool => $this->canReceive($user, $type),
        ));
    }

    public function canReceive(User $user, NotificationType $type): bool
    {
        if (! $type->isConfigurable()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return match ($type) {
            NotificationType::TaskAssigned, NotificationType::TaskReminder, NotificationType::TaskOverdue,
            NotificationType::TaskAutoCompleted, NotificationType::MeetingReminder,
            NotificationType::InstitutionActivity, NotificationType::DutyExpiring,
            NotificationType::AccessChanged => $this->hasCurrentDuty($user),
            NotificationType::MeetingCreated, NotificationType::MeetingAgendaCompleted => $this->isMeetingOverseer($user),
            NotificationType::ApprovalRequested => $this->isResourceManager($user),
            NotificationType::MemberRegistration => $this->holdsRole($user, app(FormSettings::class)->member_registration_notification_recipient_role_id),
            NotificationType::StudentRepRegistration => $this->holdsRole($user, app(AtstovavimasSettings::class)->getInstitutionManagerRoleId()),
            default => true,
        };
    }

    private function hasCurrentDuty(User $user): bool
    {
        return $user->current_duties()->exists();
    }

    private function holdsRole(User $user, int|string|null $roleId): bool
    {
        if ($roleId === null || $roleId === '') {
            return false;
        }

        return $user->current_duties()
            ->whereHas('roles', fn (Builder $query) => $query->where('id', $roleId))
            ->exists();
    }

    private function isResourceManager(User $user): bool
    {
        return $user->current_duties()
            ->whereHas('roles.permissions', fn (Builder $query) => $query->where('name', config('permission.resource_managership_indicating_permission')))
            ->exists();
    }

    private function isMeetingOverseer(User $user): bool
    {
        $settings = app(AtstovavimasSettings::class);
        $roleIds = $settings->getTenantVisibilityRoleIds()
            ->merge($settings->getGlobalVisibilityRoleIds())
            ->push($settings->getInstitutionManagerRoleId())
            ->filter()
            ->values();

        if ($roleIds->isNotEmpty()) {
            $holdsOverseerRole = $user->current_duties()->whereHas('roles', fn (Builder $query) => $query->whereIn('id', $roleIds))->exists()
                || $user->roles()->whereIn('id', $settings->getGlobalVisibilityRoleIds())->exists();

            if ($holdsOverseerRole) {
                return true;
            }
        }

        return InstitutionSecretary::query()
            ->where('user_id', $user->id)
            ->whereHas('cadence', fn (Builder $query) => $query->containing(now()->toDateString()))
            ->exists();
    }
}
