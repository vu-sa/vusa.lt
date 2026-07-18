<?php

use App\Enums\WorkspaceMemberRole;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->creator = makeUser($this->tenant);
    $this->member = makeUser($this->tenant);
    $this->outsider = makeUser($this->tenant);

    $this->workspace = Workspace::factory()->create([
        'created_by' => $this->creator->id,
        'institution_id' => null,
    ]);
    $this->workspace->members()->attach($this->creator->id, ['role' => WorkspaceMemberRole::Admin->value]);
    $this->workspace->members()->attach($this->member->id, ['role' => WorkspaceMemberRole::Member->value]);
});

describe('access control', function () {
    test('outsider cannot view a workspace', function () {
        asUser($this->outsider)
            ->get(route('workspaces.show', $this->workspace))
            ->assertStatus(403);
    });

    test('member can view a workspace', function () {
        asUser($this->member)
            ->get(route('workspaces.show', $this->workspace))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/ShowWorkspace')
                ->has('workspace')
            );
    });

    test('active institution representative can view an institution-attached workspace without membership', function () {
        $institution = $this->outsider->institutions()->first();
        $workspace = Workspace::factory()->create([
            'created_by' => $this->creator->id,
            'institution_id' => $institution->id,
        ]);

        asUser($this->outsider)
            ->get(route('workspaces.show', $workspace))
            ->assertStatus(200);
    });

    test('user with expired duty in the institution cannot view the workspace', function () {
        $institution = Institution::factory()->for($this->tenant)->create();
        $formerRep = User::factory()->hasAttached(
            Duty::factory()->for($institution),
            ['start_date' => now()->subYear(), 'end_date' => now()->subDay()]
        )->create();

        $workspace = Workspace::factory()->create([
            'created_by' => $this->creator->id,
            'institution_id' => $institution->id,
        ]);

        asUser($formerRep)
            ->get(route('workspaces.show', $workspace))
            ->assertStatus(403);
    });

    test('index lists only accessible workspaces', function () {
        asUser($this->member)
            ->get(route('workspaces.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Workspaces/IndexWorkspace')
                ->has('workspaces', 1)
            );

        asUser($this->outsider)
            ->get(route('workspaces.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->has('workspaces', 0));
    });
});

describe('workspace crud', function () {
    test('user can create a workspace attached to their own institution and becomes its admin', function () {
        $institution = $this->creator->institutions()->first();

        asUser($this->creator)
            ->post(route('workspaces.store'), [
                'name' => 'SPK pasiruošimas',
                'description' => 'Ruošiamės spalio posėdžiui',
                'institution_id' => $institution->id,
            ])
            ->assertRedirect();

        $workspace = Workspace::query()->where('name', 'SPK pasiruošimas')->first();
        expect($workspace)->not->toBeNull()
            ->and($workspace->institution_id)->toBe($institution->id)
            ->and($workspace->isAdmin($this->creator))->toBeTrue();
    });

    test('user cannot attach an institution they hold no duty in', function () {
        $foreignInstitution = Institution::factory()->for($this->tenant)->create();

        asUser($this->creator)
            ->post(route('workspaces.store'), [
                'name' => 'Svetima erdvė',
                'institution_id' => $foreignInstitution->id,
            ])
            ->assertStatus(403);
    });

    test('member can rename the workspace', function () {
        asUser($this->member)
            ->patch(route('workspaces.update', $this->workspace), [
                'name' => 'Naujas pavadinimas',
            ])
            ->assertRedirect();

        expect($this->workspace->fresh()->name)->toBe('Naujas pavadinimas');
    });

    test('non-admin member cannot change the attached institution', function () {
        $institution = $this->member->institutions()->first();

        asUser($this->member)
            ->patch(route('workspaces.update', $this->workspace), [
                'name' => $this->workspace->name,
                'institution_id' => $institution->id,
            ])
            ->assertStatus(403);
    });

    test('non-admin member cannot delete the workspace', function () {
        asUser($this->member)
            ->delete(route('workspaces.destroy', $this->workspace))
            ->assertStatus(403);
    });

    test('admin can delete the workspace', function () {
        asUser($this->creator)
            ->delete(route('workspaces.destroy', $this->workspace))
            ->assertRedirect(route('workspaces.index'));

        expect(Workspace::query()->find($this->workspace->id))->toBeNull()
            ->and(Workspace::withTrashed()->find($this->workspace->id))->not->toBeNull();
    });
});

describe('member management', function () {
    test('admin can invite a member', function () {
        $invitee = makeUser($this->tenant);

        asUser($this->creator)
            ->post(route('workspaces.members.add', $this->workspace), [
                'user_id' => $invitee->id,
                'role' => WorkspaceMemberRole::Member->value,
            ])
            ->assertRedirect();

        expect($this->workspace->isMember($invitee))->toBeTrue();
    });

    test('non-admin member cannot invite members', function () {
        $invitee = makeUser($this->tenant);

        asUser($this->member)
            ->post(route('workspaces.members.add', $this->workspace), [
                'user_id' => $invitee->id,
                'role' => WorkspaceMemberRole::Member->value,
            ])
            ->assertStatus(403);
    });

    test('the last admin cannot be removed', function () {
        asUser($this->creator)
            ->delete(route('workspaces.members.remove', [
                'workspace' => $this->workspace->id,
                'user' => $this->creator->id,
            ]))
            ->assertRedirect();

        expect($this->workspace->isAdmin($this->creator))->toBeTrue();
    });

    test('admin can remove a member', function () {
        asUser($this->creator)
            ->delete(route('workspaces.members.remove', [
                'workspace' => $this->workspace->id,
                'user' => $this->member->id,
            ]))
            ->assertRedirect();

        expect($this->workspace->fresh()->isMember($this->member))->toBeFalse();
    });
});

describe('documents api', function () {
    test('member can create a document', function () {
        asUser($this->member)
            ->postJson(route('api.v1.admin.workspaces.documents.store', $this->workspace), [
                'title' => 'Pasiruošimas posėdžiui',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        expect($this->workspace->documents()->where('title', 'Pasiruošimas posėdžiui')->exists())->toBeTrue();
    });

    test('outsider cannot create or read documents', function () {
        asUser($this->outsider)
            ->postJson(route('api.v1.admin.workspaces.documents.store', $this->workspace), [
                'title' => 'Bandymas',
            ])
            ->assertStatus(403);

        asUser($this->outsider)
            ->getJson(route('api.v1.admin.workspaces.documents.index', $this->workspace))
            ->assertStatus(403);
    });

    test('persisted document html is sanitized', function () {
        $document = WorkspaceDocument::factory()->for($this->workspace)->create();

        asUser($this->member)
            ->putJson(route('api.v1.admin.workspaces.documents.state.update', [
                'workspace' => $this->workspace->id,
                'document' => $document->id,
            ]), [
                'yjs_state' => base64_encode('state'),
                'content_html' => '<p>Sveiki</p><script>alert(1)</script>',
            ])
            ->assertStatus(200);

        $document->refresh();
        expect($document->content_html)->toContain('Sveiki')
            ->and($document->content_html)->not->toContain('<script>');
    });

    test('document state round-trips through the show endpoint', function () {
        $document = WorkspaceDocument::factory()->for($this->workspace)->create([
            'yjs_state' => base64_encode('snapshot'),
        ]);

        asUser($this->member)
            ->getJson(route('api.v1.admin.workspaces.documents.state.show', [
                'workspace' => $this->workspace->id,
                'document' => $document->id,
            ]))
            ->assertStatus(200)
            ->assertJsonPath('data.yjs_state', base64_encode('snapshot'));
    });

    test('a document belonging to another workspace is rejected', function () {
        $otherWorkspace = Workspace::factory()->create(['created_by' => $this->creator->id]);
        $foreignDocument = WorkspaceDocument::factory()->for($otherWorkspace)->create();

        asUser($this->member)
            ->getJson(route('api.v1.admin.workspaces.documents.state.show', [
                'workspace' => $this->workspace->id,
                'document' => $foreignDocument->id,
            ]))
            ->assertStatus(403);
    });
});

describe('links', function () {
    test('member can link a meeting they can view', function () {
        $institution = $this->member->institutions()->first();
        $meeting = Meeting::factory()->create();
        $meeting->institutions()->attach($institution->id);

        asUser($this->member)
            ->post(route('workspaces.links.attach', $this->workspace), [
                'linkable_type' => 'meeting',
                'linkable_id' => $meeting->id,
            ])
            ->assertRedirect();

        expect($this->workspace->links()->where('linkable_id', $meeting->id)->exists())->toBeTrue();
    });

    test('unknown linkable type is rejected by validation', function () {
        asUser($this->member)
            ->postJson(route('workspaces.links.attach', $this->workspace), [
                'linkable_type' => 'user',
                'linkable_id' => $this->outsider->id,
            ])
            ->assertStatus(422);
    });

    test('a link belonging to another workspace cannot be detached through this workspace', function () {
        $otherWorkspace = Workspace::factory()->create(['created_by' => $this->creator->id]);
        $otherWorkspace->members()->attach($this->member->id, ['role' => WorkspaceMemberRole::Member->value]);
        $meeting = Meeting::factory()->create();
        $link = $otherWorkspace->links()->create([
            'linkable_type' => Meeting::class,
            'linkable_id' => $meeting->id,
            'added_by' => $this->creator->id,
        ]);

        asUser($this->member)
            ->delete(route('workspaces.links.detach', [
                'workspace' => $this->workspace->id,
                'link' => $link->id,
            ]))
            ->assertStatus(403);

        expect($otherWorkspace->links()->whereKey($link->id)->exists())->toBeTrue();
    });
});

describe('discussions', function () {
    test('member can post a comment on the workspace', function () {
        asUser($this->member)
            ->postJson(route('api.v1.admin.comments.store', [
                'commentableType' => 'workspace',
                'commentableId' => $this->workspace->id,
            ]), [
                'body' => '<p>Diskutuojam!</p>',
            ])
            ->assertStatus(201);

        expect($this->workspace->comments()->count())->toBe(1);
    });

    test('outsider cannot post a comment on the workspace', function () {
        asUser($this->outsider)
            ->postJson(route('api.v1.admin.comments.store', [
                'commentableType' => 'workspace',
                'commentableId' => $this->workspace->id,
            ]), [
                'body' => '<p>Bandau įsilaužti</p>',
            ])
            ->assertStatus(403);
    });
});

describe('presence channel authorization', function () {
    test('member can join a document presence channel, outsider cannot', function () {
        // The default test broadcaster (`null`) authorizes everyone without ever
        // running channel callbacks; a pusher-protocol connection with dummy
        // credentials exercises the real routes/channels.php authorization. The
        // channel callbacks register on the broadcaster resolved at boot, so they
        // must be re-registered on the newly resolved pusher broadcaster.
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => 'test-app',
        ]);
        require base_path('routes/channels.php');

        $document = WorkspaceDocument::factory()->for($this->workspace)->create();

        asUser($this->member)
            ->post('/broadcasting/auth', [
                'channel_name' => "presence-workspace-documents.{$document->id}",
                'socket_id' => '1234.5678',
            ])
            ->assertStatus(200);

        asUser($this->outsider)
            ->post('/broadcasting/auth', [
                'channel_name' => "presence-workspace-documents.{$document->id}",
                'socket_id' => '1234.5678',
            ])
            ->assertStatus(403);
    });
});
