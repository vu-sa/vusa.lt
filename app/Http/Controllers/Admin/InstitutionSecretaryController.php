<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetInstitutionSecretaries;
use App\Actions\ResyncTaskAssigneesForCadence;
use App\Http\Controllers\AdminController;
use App\Http\Requests\UpdateInstitutionSecretariesRequest;
use App\Models\Cadence;
use App\Models\Institution;
use App\Models\InstitutionSecretary;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The people nominated to look after an institution, one roster per term (O22).
 *
 * A single idempotent update rather than a CRUD trio: the roster is a set the editor
 * replaces wholesale, not rows they manage individually.
 */
class InstitutionSecretaryController extends AdminController
{
    /**
     * The roster of every term that applies to this institution, for the edit form.
     *
     * Mirrors {@see CadenceController::payload()}: the institution's own overrides when
     * it has any, otherwise the global ladder it inherits.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function payload(Institution $institution): array
    {
        $cadences = $institution->cadences()->exists()
            ? Cadence::query()->forInstitution($institution->id)->get()
            : Cadence::query()->globalLadder()->get();

        $assignments = $institution->secretaryAssignments()
            ->with('user:id,name,email,profile_photo_path')
            ->get()
            ->groupBy('cadence_id');

        $today = Carbon::today();

        return $cadences
            ->sortByDesc('start_date')
            ->values()
            ->map(function (Cadence $cadence) use ($assignments, $today) {
                $secretaryList = $assignments->get($cadence->id, collect())
                    ->map(fn (InstitutionSecretary $assignment) => self::userPayload($assignment->user))
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'cadence_id' => $cadence->id,
                    'label' => $cadence->label,
                    'start_date' => $cadence->start_date->toDateString(),
                    'end_date' => $cadence->end_date->toDateString(),
                    'is_global' => $cadence->institution_id === null,
                    'is_current' => $cadence->contains($today),
                    'secretaries' => $secretaryList,
                    'administrators' => $secretaryList,
                ];
            })
            ->all();
    }

    /**
     * The secretaries shown beside a meeting: every one of its institutions,
     * resolved at the meeting's own date.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forMeetingPayload(Meeting $meeting): array
    {
        return self::usersPayload(GetInstitutionSecretaries::forMeeting($meeting));
    }

    /**
     * Users in the shape the avatar groups want.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, array<string, mixed>>
     */
    public static function usersPayload(Collection $users): array
    {
        return $users->map(fn (User $user) => self::userPayload($user))->filter()->values()->all();
    }

    /**
     * @return array{id: string, name: string, email: string|null, profile_photo_path: string|null}|null
     */
    private static function userPayload(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
        ];
    }

    /**
     * Replace the roster for one term.
     *
     * Rows are written through the model rather than `secretaries()->sync()`:
     * BelongsToMany attach/detach go through the raw query builder, so no model events
     * fire and InstitutionSecretary's access-cache invalidation would be skipped.
     * Same trap as the dutiables pivot — see .ai/rules/system.md.
     */
    public function update(UpdateInstitutionSecretariesRequest $request, Institution $institution): RedirectResponse
    {
        $cadence = $request->cadence();
        $userIds = collect($request->safe()->array('user_ids'))->unique()->values();

        $existing = $institution->secretaryAssignments()
            ->where('cadence_id', $cadence->id)
            ->get();

        $existing
            ->reject(fn (InstitutionSecretary $assignment) => $userIds->contains($assignment->user_id))
            ->each->delete();

        $userIds
            ->diff($existing->pluck('user_id'))
            ->each(fn (string $userId) => InstitutionSecretary::create([
                'institution_id' => $institution->id,
                'cadence_id' => $cadence->id,
                'user_id' => $userId,
            ]));

        // Hand the term's still-open tasks to whoever carries them now. Falling back
        // when the roster is emptied is handled by ResolveTaskAssignees itself.
        ResyncTaskAssigneesForCadence::execute($institution->fresh(), $cadence);

        return back()->with('success', $this->entityMessage('updated', 'institution'));
    }
}
