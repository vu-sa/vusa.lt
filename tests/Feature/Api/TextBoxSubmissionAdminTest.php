<?php

use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\TextBoxSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a text-box content part owned by a Page in the given tenant, so
 * submissions can be authorized against the parent page's permissions.
 */
function makeTextBoxPart(?Tenant $tenant = null): ContentPart
{
    $page = Page::factory()->for($tenant ?? Tenant::query()->first())->create();

    return ContentPart::factory()->create([
        'content_id' => $page->content_id,
        'type' => 'text-box',
        'json_content' => [],
    ]);
}

beforeEach(function () {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

describe('destroy', function () {
    test('requires auth to delete a submission', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        $submission = TextBoxSubmission::factory()->create(['content_part_id' => $contentPart->id]);

        $this->deleteJson(route('api.v1.admin.text-box-submissions.destroy', $submission))
            ->assertUnauthorized();

        $this->assertDatabaseHas('text_box_submissions', ['id' => $submission->id]);
    });

    test('forbids a user without page permission from deleting a submission', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        $submission = TextBoxSubmission::factory()->create(['content_part_id' => $contentPart->id]);

        asUser($this->user)
            ->deleteJson(route('api.v1.admin.text-box-submissions.destroy', $submission))
            ->assertStatus(403);

        $this->assertDatabaseHas('text_box_submissions', ['id' => $submission->id]);
    });

    test('can delete a single submission when authorized', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        $submission = TextBoxSubmission::factory()->create(['content_part_id' => $contentPart->id]);

        asUser($this->admin)
            ->deleteJson(route('api.v1.admin.text-box-submissions.destroy', $submission))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Atsakymas ištrintas',
            ]);

        $this->assertDatabaseMissing('text_box_submissions', ['id' => $submission->id]);
    });
});

describe('destroyAll', function () {
    test('requires auth to delete all submissions', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        TextBoxSubmission::factory()->count(3)->create(['content_part_id' => $contentPart->id]);

        $this->deleteJson(route('api.v1.admin.text-box-submissions.destroyAll'), [
            'content_part_id' => $contentPart->id,
        ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('text_box_submissions', 3);
    });

    test('forbids a user without page permission from deleting all submissions', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        TextBoxSubmission::factory()->count(3)->create(['content_part_id' => $contentPart->id]);

        asUser($this->user)
            ->deleteJson(route('api.v1.admin.text-box-submissions.destroyAll'), [
                'content_part_id' => $contentPart->id,
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('text_box_submissions', 3);
    });

    test('validates content_part_id is required', function () {
        asUser($this->admin)
            ->deleteJson(route('api.v1.admin.text-box-submissions.destroyAll'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content_part_id']);
    });

    test('can delete all submissions for a content part when authorized', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        $otherContentPart = makeTextBoxPart($this->tenant);

        TextBoxSubmission::factory()->count(3)->create(['content_part_id' => $contentPart->id]);
        $otherSubmission = TextBoxSubmission::factory()->create(['content_part_id' => $otherContentPart->id]);

        asUser($this->admin)
            ->deleteJson(route('api.v1.admin.text-box-submissions.destroyAll'), [
                'content_part_id' => $contentPart->id,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Visi atsakymai ištrinti',
            ]);

        $this->assertDatabaseCount('text_box_submissions', 1);
        $this->assertDatabaseHas('text_box_submissions', ['id' => $otherSubmission->id]);
    });
});

describe('index', function () {
    test('forbids a user without page permission from listing submissions', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        TextBoxSubmission::factory()->count(2)->create(['content_part_id' => $contentPart->id]);

        asUser($this->user)
            ->getJson(route('api.v1.admin.text-box-submissions.index', ['content_part_id' => $contentPart->id]))
            ->assertStatus(403);
    });

    test('lists submissions when authorized', function () {
        $contentPart = makeTextBoxPart($this->tenant);
        TextBoxSubmission::factory()->count(2)->create(['content_part_id' => $contentPart->id]);

        asUser($this->admin)
            ->getJson(route('api.v1.admin.text-box-submissions.index', ['content_part_id' => $contentPart->id]))
            ->assertOk()
            ->assertJson(['success' => true]);
    });
});
