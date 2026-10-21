<?php

namespace App\Services\Notifications;

use App\Actions\GetResourceManagers;
use App\Enums\NotificationType;
use App\Enums\ResponsibilityPurpose;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionSecretary;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\ResponsibilityResolver;
use App\Settings\AtstovavimasSettings;
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
            // Reps carrying the agenda task hear about a new meeting; the filled-in agenda is their own work.
            NotificationType::MeetingCreated => $this->hasCurrentDuty($user) || $this->isMeetingOverseer($user),
            NotificationType::MeetingAgendaCompleted => $this->isMeetingOverseer($user),
            NotificationType::ApprovalRequested => $this->isResourceManager($user),
            NotificationType::MemberRegistration => app(ResponsibilityResolver::class)->holdsAnywhere($user, ResponsibilityPurpose::MemberRegistration),
            NotificationType::StudentRepRegistration => $this->coordinates($user),
            // Whoever may send "Ar vyko posėdis?" hears back when it reached the wrong person.
            NotificationType::InstitutionActivityNotMine => $user->can('viewAny', InstitutionCheckIn::class),
            NotificationType::SupportRequestCreated => $user->can('viewAny', SupportRequest::class),
            default => true,
        };
    }

    private function hasCurrentDuty(User $user): bool
    {
        return $user->current_duties()->exists();
    }

    private function isResourceManager(User $user): bool
    {
        return $user->current_duties()
            ->whereHas('roles.permissions', fn (Builder $query) => $query->whereIn('name', GetResourceManagers::permissionNames()))
            ->exists();
    }

    private function coordinates(User $user): bool
    {
        return app(ResponsibilityResolver::class)->holdsAnywhere($user, ResponsibilityPurpose::StudentRepCoordination);
    }

    private function isMeetingOverseer(User $user): bool
    {
        if ($this->coordinates($user)) {
            return true;
        }

        $settings = app(AtstovavimasSettings::class);
        $roleIds = $settings->getTenantVisibilityRoleIds()
            ->merge($settings->getGlobalVisibilityRoleIds())
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
