<?php

namespace App\Policies;

use App\Models\Duty;
use App\Models\DutyType;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy for FileableFile model.
 *
 * File permissions are delegated to the parent fileable model's policy.
 * This ensures consistent access control across the application.
 */
class FileableFilePolicy
{
    use HandlesAuthorization;

    public function __construct(private ModelAuthorizer $authorizer) {}

    /**
     * Determine whether the user can view any files.
     */
    public function viewAny(User $user): bool
    {
        // Users can view files if they can view any of the supported fileable types
        return true;
    }

    /**
     * Determine whether the user can view the file.
     * Delegates to the parent fileable's view permission.
     */
    public function view(User $user, FileableFile $fileableFile): bool
    {
        /** @var Model|null $fileable */
        $fileable = $fileableFile->fileable()->first();

        if (! $fileable) {
            return false;
        }

        if ($fileable instanceof InstitutionType || $fileable instanceof DutyType) {
            return $user->can('view', $fileable) || $this->reachesOwnerOfType($user, $fileable);
        }

        return $user->can('view', $fileable);
    }

    /**
     * Type files are reference documents shown on every duty or institution of the type or a child type,
     * so whoever reaches one of those may open them, not only the dictionary's editors.
     */
    private function reachesOwnerOfType(User $user, InstitutionType|DutyType $type): bool
    {
        $isDutyType = $type instanceof DutyType;
        $resource = $isDutyType ? 'duties' : 'institutions';
        $owners = ($isDutyType ? Duty::query() : Institution::query())
            ->whereHas('types', fn (Builder $query) => $query->whereKey($type->getDescendantsAndSelf()->modelKeys()));

        if ($this->authorizer->allows($user, "{$resource}.read.*")) {
            return $owners->exists();
        }

        $ownIds = $user->authorization_duties()->pluck($isDutyType ? 'duties.id' : 'duties.institution_id');
        $tenantIds = $this->authorizer->tenants($user, "{$resource}.read.padalinys")->modelKeys();

        return $owners->where(function (Builder $query) use ($ownIds, $tenantIds, $isDutyType): void {
            $query->whereKey($ownIds);

            if ($tenantIds !== []) {
                $isDutyType
                    ? $query->orWhereHas('institution', fn (Builder $institution) => $institution->whereIn('tenant_id', $tenantIds))
                    : $query->orWhereIn('tenant_id', $tenantIds);
            }
        })->exists();
    }

    /**
     * Determine whether the user can create files.
     * Requires ability to update the target fileable.
     */
    public function create(User $user): bool
    {
        // Creation is checked against the specific fileable in the controller
        return true;
    }

    /**
     * Determine whether the user can update the file.
     * Delegates to the parent fileable's update permission.
     */
    public function update(User $user, FileableFile $fileableFile): bool
    {
        /** @var Model|null $fileable */
        $fileable = $fileableFile->fileable()->first();

        if (! $fileable) {
            return false;
        }

        return $user->can('update', $fileable);
    }

    /**
     * Determine whether the user can delete the file.
     * Delegates to the parent fileable's update permission.
     */
    public function delete(User $user, FileableFile $fileableFile): bool
    {
        /** @var Model|null $fileable */
        $fileable = $fileableFile->fileable()->first();

        if (! $fileable) {
            return false;
        }

        // Deleting a file requires update permission on the fileable
        return $user->can('update', $fileable);
    }
}
