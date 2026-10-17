<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\AddImageMedia;
use App\Enums\SupportRequestStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\AssignSupportRequestRequest;
use App\Http\Requests\IndexSupportRequestRequest;
use App\Http\Requests\SyncSupportRequestInvolvedUsersRequest;
use App\Http\Requests\UpdateSupportRequestRequest;
use App\Http\Requests\UpdateSupportRequestStatusRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportRequestAssignedNotification;
use App\Notifications\SupportRequestInvolvedNotification;
use App\Notifications\SupportRequestStatusChangedNotification;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
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
            'involvedUsers:users.id,users.name,users.profile_photo_path',
            'media',
        ]);

        $roleUsers = $supportRequest->roleUsers()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'profile_photo_path' => $u->profile_photo_path,
        ]);

        $media = $supportRequest->getMedia('evidence')->map(fn (Media $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'file_name' => $m->file_name,
            'mime_type' => $m->mime_type,
            'size' => $m->size,
            'original_url' => $m->getUrl(),
            'thumb_url' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl(),
            'preview_url' => $m->hasGeneratedConversion('large') ? $m->getUrl('large') : $m->getUrl(),
        ]);

        $assignees = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'profile_photo_path']);

        $user = auth()->user();

        return [
            'supportRequest' => [
                ...$supportRequest->toArray(),
                // toArray() snake-cases the assignedTo relation onto the assigned_to id column.
                'assigned_to' => $supportRequest->assigned_to,
                'assignedTo' => $supportRequest->assignedTo,
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
                'can_manage_involved' => $user?->can('assign', $supportRequest) ?? false,
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
            // Through the model, so the files go too; a query delete left them on disk.
            $supportRequest->getMedia('evidence')
                ->whereIn('id', $request->validated('deleted_media_ids'))
                ->each(fn (Media $media) => $media->delete());
        }

        foreach ($request->file('images', []) as $image) {
            app(AddImageMedia::class)->execute($supportRequest, $image, 'evidence');
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

        if ($oldStatus !== $newStatus) {
            $recipients = collect([$supportRequest->creator])
                ->concat($supportRequest->involvedUsers)
                ->filter()
                ->unique('id')
                ->reject(fn (User $user) => $user->is($request->user()));

            Notification::send($recipients, new SupportRequestStatusChangedNotification(
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
        $previousAssignee = $supportRequest->assigned_to;
        $supportRequest->update(['assigned_to' => $request->validated('assigned_to')]);

        $assignee = $supportRequest->assignedTo;
        if ($assignee && $assignee->id !== $previousAssignee && $assignee->id !== $request->user()->id) {
            $assignee->notify(new SupportRequestAssignedNotification($supportRequest, $request->user()));
        }

        return back()->with('success', $this->entityMessage('updated', 'supportRequest'));
    }

    public function syncInvolvedUsers(SyncSupportRequestInvolvedUsersRequest $request, SupportRequest $supportRequest): RedirectResponse
    {
        $changes = $supportRequest->involvedUsers()->sync($request->validated('involved_users'));

        Notification::send(
            User::query()->whereKey($changes['attached'])->whereKeyNot($request->user()->id)->get(),
            new SupportRequestInvolvedNotification($supportRequest, $request->user()),
        );

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
