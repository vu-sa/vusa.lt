<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\SyncImageMedia;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Dutiables\DestroyDutiableRequest;
use App\Http\Requests\StoreDutiableRequest;
use App\Http\Requests\UpdateDutiableRequest;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Duties\DutyAssignmentService;
use App\Support\Dutiables\DutyAssignmentActor;
use App\Support\MorphMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DutiableController extends AdminController
{
    /**
     * Whether the given dutiable belongs to the currently authenticated user,
     * meaning a change to it could affect their own access.
     */
    private function affectsActingUser(Dutiable $dutiable, Request $request): bool
    {
        // Super admins short-circuit every permission, so duty changes can never
        // lock them out — no need to analyze.
        if ($request->user()->isSuperAdmin()) {
            return false;
        }

        return $dutiable->dutiable_type === MorphMap::alias(User::class)
            && (string) $dutiable->dutiable_id === (string) $request->user()->id;
    }

    // Authorized in StoreDutiableRequest: the duty and member named in the body.
    public function store(StoreDutiableRequest $request, SyncImageMedia $syncImage, DutyAssignmentService $assignments): RedirectResponse
    {
        $data = $request->safe();
        $duty = Duty::query()->findOrFail($data['duty_id']);
        $tenantId = $request->delegatedTenantId();

        $userId = DB::transaction(function () use ($request, $data, $duty, $tenantId, $syncImage, $assignments): string {
            $assignments->lockDuty($duty);
            $userId = $request->isCreatingMember()
                ? User::create([
                    'name' => $data['new_user']['name'],
                    'email' => $data['new_user']['email'] ?? null,
                    'phone' => $data['new_user']['phone'] ?? null,
                ])->id
                : $data['user_id'];

            $dutiable = $assignments->startTerm($duty, $userId, $tenantId, [
                ...$data->only([
                    'start_date', 'end_date', 'study_program_id', 'study_program_note',
                    'additional_email', 'description',
                ]),
                'use_original_duty_name' => $data['use_original_duty_name'] ?? false,
            ], actor: $request->user());

            if (! $request->choosesQuotaTenant() && ($dutiable->end_date === null || $dutiable->end_date->gte(today()))) {
                $assignments->enforceTenantQuota($duty, $tenantId, $request->user(), 'user_id');
            }

            $syncImage->fromValidated($dutiable, 'photo', $request->validated(), 'photo_media', $request->user());

            return $userId;
        });

        return $this->savedResponse('created', $tenantId, $userId);
    }

    /**
     * The standalone edit page is gone (Decision O21): a term is edited in the Priskirti sheet
     * on its duty's record, which opens itself for `?dutiable=`.
     *
     * @deprecated Kept for bookmarks, mail links and the timeline's `edit_url`; remove once they open the occupancy sheet instead.
     */
    #[Authorize('manageDutiable', 'dutiable')]
    public function edit(Dutiable $dutiable): RedirectResponse
    {
        return redirect()->route('duties.show', ['duty' => $dutiable->duty_id, 'dutiable' => $dutiable->id]);
    }

    /**
     * Update the specified resource in storage.
     * TODO: this will not work for contacts
     */
    #[Authorize('manageDutiable', 'dutiable')]
    public function update(Dutiable $dutiable, UpdateDutiableRequest $request, DutyAssignmentService $assignments)
    {
        $mutation = fn () => DB::transaction(function () use ($dutiable, $request, $assignments): void {
            $duty = $dutiable->duty()->withTrashed()->firstOrFail();
            $assignments->lockDuty($duty);
            $dutiable->refreshForUpdate();
            $this->authorize('manageDutiable', $dutiable);

            $data = $request->validated();
            $storedStart = $dutiable->start_date->toDateString();
            $storedEnd = $dutiable->end_date?->toDateString();
            $storedTenant = $dutiable->tenant_id;

            if ($dutiable->via_dutiable_id !== null) {
                unset($data['start_date'], $data['end_date'], $data['tenant_id']);
            } else {
                if (isset($data['tenant_id'])) {
                    $data['tenant_id'] = (int) $data['tenant_id'];
                }
                if (($data['start_date'] ?? $storedStart) === $storedStart) {
                    unset($data['start_date']);
                }
                if (array_key_exists('end_date', $data) && $data['end_date'] === $storedEnd) {
                    unset($data['end_date']);
                }
                if (array_key_exists('tenant_id', $data) && $data['tenant_id'] === $storedTenant) {
                    unset($data['tenant_id']);
                }
            }

            $endDate = array_key_exists('end_date', $data) ? $data['end_date'] : $storedEnd;
            $tenantId = array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $storedTenant;
            $attribute = array_key_exists('tenant_id', $data) ? 'tenant_id' : 'end_date';
            $termChanged = Arr::hasAny($data, ['start_date', 'end_date', 'tenant_id']);
            if ($termChanged) {
                $assignments->validateTermEdit($dutiable, $data['start_date'] ?? null, $endDate, $tenantId, $attribute);
            }
            $allocatesSeat = $termChanged && $assignments->allocatesSeat($dutiable, $endDate, $tenantId);

            $affectedDuties = collect([$duty])->concat($duty->exOfficioTargetDuties);
            $members = collect([$dutiable->user])->filter()->values();
            $assignments->auditMemberships($affectedDuties, $members, function () use ($dutiable, $duty, $data, $request, $assignments, $tenantId, $attribute, $allocatesSeat): void {
                DutyAssignmentActor::as($request->user(), fn () => $dutiable->fill(Arr::except($data, ['photo_media']))->save());
                if ($allocatesSeat) {
                    $assignments->enforceTenantQuota($duty, $tenantId, $request->user(), $attribute);
                }

                app(SyncImageMedia::class)->fromValidated($dutiable, 'photo', $data, 'photo_media', $request->user());
            });
        });

        $couldAffectSelf = $this->affectsActingUser($dutiable, $request);

        if ($warning = $this->guardSelfLockout($request->user(), $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return $this->savedResponse('updated', $dutiable->tenant_id, $dutiable->dutiable_id);
    }

    /**
     * A padalinys's admins cannot edit a quota seat if its member has no non-ended term
     * in that padalinys (DutyPolicy::managePeople).
     */
    private function savedResponse(string $action, ?int $tenantId, string $userId): RedirectResponse
    {
        $response = back()->with('success', $this->entityMessage($action, 'dutiable'));
        $member = $tenantId !== null ? User::query()->find($userId) : null;

        if ($member === null || $member->current_or_upcoming_tenants()->where('tenants.id', $tenantId)->exists()) {
            return $response;
        }

        return $response->with('toast_description', __('dutiables.assign.quota_tenant_not_member', [
            'name' => $member->name,
            'tenant' => Tenant::query()->whereKey($tenantId)->value('shortname') ?? (string) $tenantId,
        ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('manageDutiable', 'dutiable')]
    public function destroy(DestroyDutiableRequest $request, Dutiable $dutiable, DutyAssignmentService $assignments)
    {
        $user = $dutiable->dutiable;

        $mutation = fn () => DB::transaction(function () use ($dutiable, $assignments, $request): void {
            $duty = $dutiable->duty()->withTrashed()->firstOrFail();
            $derivedDutyIds = $dutiable->derivedDutiables()->select('duty_id');
            $affectedDuties = Duty::withTrashed()->whereIn('id', $derivedDutyIds)->get()->push($duty);
            $assignments->lockDuties($affectedDuties);
            $dutiable->refreshForUpdate();
            $this->authorize('manageDutiable', $dutiable);
            $members = collect([$dutiable->user])->filter()->values();
            $assignments->auditMemberships($affectedDuties, $members,
                fn () => DutyAssignmentActor::as($request->user(), fn () => $dutiable->delete()));
        });

        if ($warning = $this->guardSelfLockout($request->user(), $this->affectsActingUser($dutiable, $request), $request, $mutation)) {
            return $warning;
        }

        $message = $this->entityMessage('deleted', 'dutiable');

        // The timeline deletes rows in place and must not be navigated away from; a request
        // that came from the (now retired) standalone page has to leave, because its record is gone.
        return $request->safe()->boolean('stay')
            ? back()->with('success', $message)
            : redirect()->route('users.show', $user)->with('success', $message);
    }
}
