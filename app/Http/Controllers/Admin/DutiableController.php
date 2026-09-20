<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreDutiableRequest;
use App\Http\Requests\UpdateDutiableRequest;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    /**
     * Assign a member to a duty (Decision O21: the record page's Priskirti sheet).
     *
     * Goes through Dutiable::create() rather than the users() relation so the
     * description reaches the model's sanitizing setter; the audit wrapper keeps the
     * activity log identical to the wizard's attach.
     */
    public function store(StoreDutiableRequest $request): RedirectResponse
    {
        $data = $request->safe();
        $duty = Duty::query()->findOrFail($data['duty_id']);

        $duty->auditRelationChange('users', fn () => Dutiable::create([
            ...$data->only([
                'duty_id', 'start_date', 'end_date', 'study_program_id', 'study_program_note',
                'additional_email', 'additional_photo', 'additional_photo_focal_point', 'description',
            ]),
            'dutiable_id' => $data['user_id'],
            'dutiable_type' => MorphMap::alias(User::class),
            'tenant_id' => $request->delegatedTenantId(),
            'use_original_duty_name' => $data['use_original_duty_name'] ?? false,
        ]));

        return back()->with('success', $this->entityMessage('created', 'dutiable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dutiable $dutiable)
    {
        $this->authorize('manageDutiable', $dutiable);

        $dutiable->loadMissing('duty.institution');
        $tenantId = $dutiable->duty?->institution?->tenant_id;

        // Scoped to the duty's own tenant when it's known (16 tenants have study
        // programs, ~10 each — loading every tenant's ~148 was pointless noise in
        // the picker). Falls back to the full list when the tenant can't be
        // resolved, and always keeps whatever is already selected in scope even
        // if it belongs to another tenant, so an existing cross-tenant value
        // still resolves to a real option instead of silently vanishing.
        $studyPrograms = StudyProgram::query()
            ->when($tenantId, function ($query) use ($tenantId, $dutiable): void {
                $query->where(function ($query) use ($tenantId, $dutiable): void {
                    $query->where('tenant_id', $tenantId);

                    if ($dutiable->study_program_id) {
                        $query->orWhere('id', $dutiable->study_program_id);
                    }
                });
            })
            ->get();

        return $this->inertiaResponse('Admin/People/EditDutiable', [
            'dutiable' => $dutiable->load('duty', 'dutiable', 'viaDutiable.duty')->toFullArray(),
            'studyPrograms' => $studyPrograms,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * TODO: this will not work for contacts
     */
    public function update(Dutiable $dutiable, UpdateDutiableRequest $request)
    {
        $this->authorize('manageDutiable', $dutiable);

        $data = $request->validated();

        // Derived (ex-officio) rows have their dates mirrored from the source;
        // do not allow manual overrides.
        if (! is_null($dutiable->via_dutiable_id)) {
            unset($data['start_date'], $data['end_date']);
        }

        $mutation = fn () => $dutiable->fill($data)->save();

        // Email-only edits (JSON) never change the active period, so they can't
        // affect access; only guard the date-editing (Inertia) path.
        $couldAffectSelf = ! $request->wantsJson() && $this->affectsActingUser($dutiable, $request);

        if ($warning = $this->guardSelfLockout($request->user(), $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.duty.email_updated'),
            ]);
        }

        return back()->with('success', $this->entityMessage('updated', 'dutiable'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dutiable $dutiable, Request $request)
    {
        $this->authorize('manageDutiable', $dutiable);

        $user = $dutiable->dutiable;

        $mutation = fn () => $dutiable->delete();

        if ($warning = $this->guardSelfLockout($request->user(), $this->affectsActingUser($dutiable, $request), $request, $mutation)) {
            return $warning;
        }

        $message = $this->entityMessage('deleted', 'dutiable');

        // The timeline deletes rows in place and must not be navigated away from; the
        // dutiable edit page has to leave, because the record it was editing is gone.
        return $request->boolean('stay')
            ? back()->with('success', $message)
            : redirect()->route('users.edit', $user)->with('success', $message);
    }
}
