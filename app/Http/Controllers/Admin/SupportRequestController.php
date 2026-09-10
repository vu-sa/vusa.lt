<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Http\Controllers\AdminController;
use App\Http\Requests\AssignSupportRequestRequest;
use App\Http\Requests\IndexSupportRequestRequest;
use App\Http\Requests\UpdateSupportRequestRequest;
use App\Http\Requests\UpdateSupportRequestStatusRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportRequestStatusChangedNotification;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SupportRequestController extends AdminController
{
    use HandlesSoftDeletes;

    public function __construct(public Authorizer $authorizer) {}

    public function index(IndexSupportRequestRequest $request): RedirectResponse
    {
        $this->handleAuthorization('viewAny', SupportRequest::class);

        return redirect()->route('mySupportRequests.index', ['tab' => 'all']);
    }

    public function show(SupportRequest $supportRequest): Response
    {
        $this->authorize('view', $supportRequest);

        return $this->inertiaResponse('Admin/SupportRequests/ShowSupportRequest', $this->showPayload($supportRequest));
    }

    public function edit(SupportRequest $supportRequest): Response
    {
        $this->authorize('update', $supportRequest);

        return $this->inertiaResponse('Admin/SupportRequests/EditSupportRequest', [
            ...$this->showPayload($supportRequest),
            'isEditing' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function showPayload(SupportRequest $supportRequest): array
    {
        $supportRequest->load([
            'creator:id,name,email,profile_photo_path',
            'assignedTo:id,name,email,profile_photo_path',
            'type',
            'area',
            'service',
            'roles.users:users.id,users.name,users.profile_photo_path',
            'roles.currentUsersThroughDuties:users.id,users.name,users.profile_photo_path',
            'media',
        ]);

        $roleUsers = collect();
        if ($supportRequest->visibility === SupportRequestVisibility::Roles) {
            $roleUsers = $supportRequest->roles
                ->flatMap(fn ($r) => $r->users->concat($r->currentUsersThroughDuties))
                ->unique('id')
                ->values()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'profile_photo_path' => $u->profile_photo_path,
                ]);
        }

        $media = $supportRequest->getMedia('evidence')->map(fn (Media $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'file_name' => $m->file_name,
            'mime_type' => $m->mime_type,
            'size' => $m->size,
            'original_url' => $m->getUrl(),
            'thumb_url' => $m->getUrl('thumb'),
            'preview_url' => $m->getUrl('preview'),
        ]);

        $assignees = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'profile_photo_path']);

        $user = auth()->user();

        return [
            'supportRequest' => [
                ...$supportRequest->toArray(),
                'media' => $media,
                'role_users' => $roleUsers,
            ],
            'availableStatuses' => collect(SupportRequestStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'badgeVariant' => $case->badgeVariant(),
            ]),
            'assignees' => $assignees,
            'permissions' => [
                'can_update' => $user?->can('update', $supportRequest) ?? false,
                'can_update_status' => $user?->can('updateStatus', $supportRequest) ?? false,
                'can_assign' => $user?->can('assign', $supportRequest) ?? false,
                'can_delete' => $user?->can('delete', $supportRequest) ?? false,
                'can_restore' => $user?->can('restore', $supportRequest) ?? false,
            ],
            ...MySupportRequestController::formOptions($user, $supportRequest),
        ];
    }

    public function update(UpdateSupportRequestRequest $request, SupportRequest $supportRequest): RedirectResponse
    {
        $data = $request->safe()->except(['roles', 'images', 'deleted_media_ids']);
        $supportRequest->update($data);

        if ($request->has('roles')) {
            $supportRequest->roles()->sync($request->validated('roles', []));
        }

        if ($request->filled('deleted_media_ids')) {
            Media::query()
                ->whereIn('id', $request->input('deleted_media_ids'))
                ->where('model_type', $supportRequest->getMorphClass())
                ->where('model_id', $supportRequest->id)
                ->delete();
        }

        foreach ($request->file('images', []) as $image) {
            $supportRequest->addMedia($image)->toMediaCollection('evidence');
        }

        return redirect()->route('supportRequests.show', $supportRequest)->with('success', $this->entityMessage('updated', 'supportRequest'));
    }

    public function updateStatus(UpdateSupportRequestStatusRequest $request, SupportRequest $supportRequest): RedirectResponse
    {
        $oldStatus = $supportRequest->status;
        $newStatus = SupportRequestStatus::from($request->validated('status'));

        $updates = ['status' => $newStatus];

        if ($newStatus->isTerminal()) {
            $updates['resolved_at'] = now();
        } elseif ($oldStatus->isTerminal()) {
            $updates['resolved_at'] = null;
        }

        $supportRequest->update($updates);

        if ($supportRequest->creator && $oldStatus !== $newStatus) {
            $supportRequest->creator->notify(new SupportRequestStatusChangedNotification(
                $supportRequest,
                $oldStatus,
                $newStatus,
                $request->user()
            ));
        }

        return back()->with('success', $this->entityMessage('updated', 'supportRequest'));
    }

    public function assign(AssignSupportRequestRequest $request, SupportRequest $supportRequest): RedirectResponse
    {
        $supportRequest->update(['assigned_to' => $request->validated('assigned_to')]);

        return back()->with('success', $this->entityMessage('updated', 'supportRequest'));
    }

    public function destroy(SupportRequest $supportRequest): RedirectResponse
    {
        $this->authorize('delete', $supportRequest);
        $supportRequest->delete();

        return redirect()->route('mySupportRequests.index', ['tab' => 'all'])->with('success', $this->entityMessage('deleted', 'supportRequest'));
    }

    public function restore(SupportRequest $supportRequest): RedirectResponse
    {
        return $this->restoreModel($supportRequest);
    }
}
