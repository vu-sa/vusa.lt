<?php

use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\SupportRequestArea;
use App\Models\SupportRequestType;
use App\Models\SupportService;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SupportRequestStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);

    $this->service = SupportService::factory()->create();
    $this->type = SupportRequestType::factory()->create();
    $this->area = SupportRequestArea::factory()->create(['support_service_id' => $this->service->id]);

    $this->supportRequest = SupportRequest::factory()->create([
        'support_service_id' => $this->service->id,
        'support_request_type_id' => $this->type->id,
        'support_request_area_id' => $this->area->id,
        'created_by' => $this->user->id,
        'status' => SupportRequestStatus::New,
        'visibility' => SupportRequestVisibility::Private,
    ]);
});

describe('unauthorized access', function (): void {
    test('regular user cannot view queue index', function (): void {
        asUser($this->user)->get(route('supportRequests.index'))->assertStatus(403);
    });

    test('regular user cannot view another users private support request', function (): void {
        $otherUser = makeUser($this->tenant);
        $otherRequest = SupportRequest::factory()->create([
            'created_by' => $otherUser->id,
            'visibility' => SupportRequestVisibility::Private,
        ]);

        asUser($this->user)->get(route('supportRequests.show', $otherRequest->id))->assertStatus(403);
    });

    test('regular user cannot update status', function (): void {
        asUser($this->user)->patch(route('supportRequests.status.update', $this->supportRequest->id), [
            'status' => 'reviewing',
        ])->assertStatus(403);
    });

    test('regular user cannot assign user', function (): void {
        asUser($this->user)->patch(route('supportRequests.assign', $this->supportRequest->id), [
            'assigned_to' => $this->user->id,
        ])->assertStatus(403);
    });

    test('regular user cannot delete support request', function (): void {
        asUser($this->user)->delete(route('supportRequests.destroy', $this->supportRequest->id))->assertStatus(403);
    });
});

describe('authorized manager access', function (): void {
    test('manager queue entry redirects to the unified dashboard', function (): void {
        asUser($this->admin)->get(route('supportRequests.index'))
            ->assertRedirect(route('mySupportRequests.index', ['tab' => 'all']));
    });

    test('can view support request show page', function (): void {
        asUser($this->admin)->get(route('supportRequests.show', $this->supportRequest->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SupportRequests/ShowSupportRequest')
                ->has('supportRequest')
                ->where('supportRequest.id', $this->supportRequest->id)
                ->has('availableStatuses')
                ->has('assignees')
                ->where('permissions.can_update_status', true)
                ->where('permissions.can_assign', true)
                ->where('permissions.can_delete', true)
            );
    });

    test('role_users on show page only includes current duty holders and not past users', function (): void {
        $role = Role::create(['name' => 'Reviewer Role', 'guard_name' => 'web']);
        $duty = Duty::factory()->create();
        $duty->roles()->attach($role->id);

        $pastUser = makeUser($this->tenant);
        $duty->users()->attach($pastUser->id, [
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);

        $currentUser = makeUser($this->tenant);
        $duty->users()->attach($currentUser->id, [
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

        $this->supportRequest->update(['visibility' => SupportRequestVisibility::Roles]);
        $this->supportRequest->roles()->sync([$role->id]);

        asUser($this->admin)->get(route('supportRequests.show', $this->supportRequest->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('supportRequest.role_users', function ($users) use ($currentUser, $pastUser) {
                    $userIds = collect($users)->pluck('id');

                    return $userIds->contains($currentUser->id) && ! $userIds->contains($pastUser->id);
                })
            );
    });

    test('can update status and sets resolved_at and notifies creator', function (): void {
        Notification::fake();

        asUser($this->admin)->patch(route('supportRequests.status.update', $this->supportRequest->id), [
            'status' => 'done',
        ])->assertRedirect();

        $this->supportRequest->refresh();
        expect($this->supportRequest->status)->toBe(SupportRequestStatus::Done)
            ->and($this->supportRequest->resolved_at)->not->toBeNull();

        Notification::assertSentTo(
            $this->user,
            SupportRequestStatusChangedNotification::class,
            fn (SupportRequestStatusChangedNotification $notification) => $notification->newStatus->value === 'done'
                && $notification->body($this->user) === $this->admin->name.' pakeitė užklausos „'.$this->supportRequest->title.'“ būseną į Išspręsta.'
        );
    });

    test('reopening clears resolved_at', function (): void {
        $this->supportRequest->update([
            'status' => SupportRequestStatus::Done,
            'resolved_at' => now(),
        ]);

        asUser($this->admin)->patch(route('supportRequests.status.update', $this->supportRequest->id), [
            'status' => 'in_progress',
        ])->assertRedirect();

        $this->supportRequest->refresh();
        expect($this->supportRequest->status)->toBe(SupportRequestStatus::InProgress)
            ->and($this->supportRequest->resolved_at)->toBeNull();
    });

    test('can assign support request to a user', function (): void {
        asUser($this->admin)->patch(route('supportRequests.assign', $this->supportRequest->id), [
            'assigned_to' => $this->admin->id,
        ])->assertRedirect();

        $this->supportRequest->refresh();
        expect($this->supportRequest->assigned_to)->toBe($this->admin->id);
    });

    test('can unassign support request', function (): void {
        $this->supportRequest->update(['assigned_to' => $this->admin->id]);

        asUser($this->admin)->patch(route('supportRequests.assign', $this->supportRequest->id), [
            'assigned_to' => null,
        ])->assertRedirect();

        $this->supportRequest->refresh();
        expect($this->supportRequest->assigned_to)->toBeNull();
    });

    test('can soft-delete and restore support request', function (): void {
        asUser($this->admin)->delete(route('supportRequests.destroy', $this->supportRequest->id))
            ->assertRedirect(route('mySupportRequests.index', ['tab' => 'all']));

        expect(SupportRequest::find($this->supportRequest->id))->toBeNull()
            ->and(SupportRequest::withTrashed()->find($this->supportRequest->id))->not->toBeNull();

        asUser($this->admin)->post(route('supportRequests.restore', $this->supportRequest->id))
            ->assertRedirect();

        expect(SupportRequest::find($this->supportRequest->id))->not->toBeNull();
    });
});

describe('role-based and public visibility', function (): void {
    test('public support request is viewable by any authenticated user', function (): void {
        $otherUser = makeUser($this->tenant);
        $publicRequest = SupportRequest::factory()->create([
            'visibility' => SupportRequestVisibility::Public,
            'created_by' => $otherUser->id,
        ]);

        asUser($this->user)->get(route('supportRequests.show', $publicRequest->id))->assertStatus(200);
    });

    test('roles-based support request is accessible by user with direct role', function (): void {
        $role = Role::create(['name' => 'IT Coordinator', 'guard_name' => 'web']);
        $roleRequest = SupportRequest::factory()->create([
            'visibility' => SupportRequestVisibility::Roles,
            'created_by' => makeUser($this->tenant)->id,
        ]);
        $roleRequest->roles()->attach($role);

        // User without role cannot access
        asUser($this->user)->get(route('supportRequests.show', $roleRequest->id))->assertStatus(403);

        // Assign role directly to user
        $this->user->assignRole($role);

        asUser($this->user)->get(route('supportRequests.show', $roleRequest->id))->assertStatus(200);
    });

    test('roles-based support request is accessible by user with role on active duty', function (): void {
        $role = Role::create(['name' => 'Academic Coordinator', 'guard_name' => 'web']);
        $roleRequest = SupportRequest::factory()->create([
            'visibility' => SupportRequestVisibility::Roles,
            'created_by' => makeUser($this->tenant)->id,
        ]);
        $roleRequest->roles()->attach($role);

        $duty = Duty::factory()->has(Institution::factory()->state(['tenant_id' => $this->tenant->id]))
            ->hasAttached($this->user, ['start_date' => now()->subDay(), 'end_date' => now()->addDays(1)])
            ->create();
        $duty->assignRole($role);

        asUser($this->user)->get(route('supportRequests.show', $roleRequest->id))->assertStatus(200);
    });
});
