<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MergeUsers
{
    /**
     * Merge two user accounts, transferring all relationships from the merged user to the kept user.
     *
     * @param  User  $keptUser  The user account to keep
     * @param  User  $mergedUser  The user account to merge and delete
     */
    public static function execute(User $keptUser, User $mergedUser): void
    {
        DB::transaction(function () use ($keptUser, $mergedUser) {
            // Transfer duties (update pivot table)
            foreach ($mergedUser->duties as $duty) {
                $mergedUser->duties()->updateExistingPivot($duty->id, ['dutiable_id' => $keptUser->id]);
            }

            // Transfer tasks
            $mergedUser->tasks()->update(['user_id' => $keptUser->id]);

            // Transfer memberships
            $mergedUser->memberships()->update(['user_id' => $keptUser->id]);

            // Transfer reservations
            $mergedUser->reservations()->update(['user_id' => $keptUser->id]);

            // Transfer comments
            Comment::query()->where('user_id', $mergedUser->id)->update(['user_id' => $keptUser->id]);

            // Transfer institution check-ins
            InstitutionCheckIn::query()->where('user_id', $mergedUser->id)->update(['user_id' => $keptUser->id]);

            // Transfer training participations
            DB::table('training_user')
                ->where('user_id', $mergedUser->id)
                ->update(['user_id' => $keptUser->id]);

            // Transfer training organizer role
            DB::table('trainings')
                ->where('organizer_id', $mergedUser->id)
                ->update(['organizer_id' => $keptUser->id]);

            // Finally, delete the merged user
            $mergedUser->forceDelete();
        });
    }
}
