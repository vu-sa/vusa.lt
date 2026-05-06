<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\TextBoxSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTextBoxPart(): ContentPart
{
    $content = Content::factory()->create();

    return ContentPart::factory()->create([
        'content_id' => $content->id,
        'type' => 'text-box',
        'json_content' => [],
    ]);
}

describe('destroy', function () {
    test('requires auth to delete a submission', function () {
        $contentPart = makeTextBoxPart();
        $submission = TextBoxSubmission::factory()->create(['content_part_id' => $contentPart->id]);

        $this->deleteJson(route('api.v1.admin.text-box-submissions.destroy', $submission))
            ->assertUnauthorized();

        $this->assertDatabaseHas('text_box_submissions', ['id' => $submission->id]);
    });

    test('can delete a single submission', function () {
        $user = User::factory()->create();
        $contentPart = makeTextBoxPart();
        $submission = TextBoxSubmission::factory()->create(['content_part_id' => $contentPart->id]);

        $this->actingAs($user)
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
        $contentPart = makeTextBoxPart();
        TextBoxSubmission::factory()->count(3)->create(['content_part_id' => $contentPart->id]);

        $this->deleteJson(route('api.v1.admin.text-box-submissions.destroyAll'), [
            'content_part_id' => $contentPart->id,
        ])
            ->assertUnauthorized();

        $this->assertDatabaseCount('text_box_submissions', 3);
    });

    test('validates content_part_id is required', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('api.v1.admin.text-box-submissions.destroyAll'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content_part_id']);
    });

    test('can delete all submissions for a content part', function () {
        $user = User::factory()->create();
        $contentPart = makeTextBoxPart();
        $otherContentPart = makeTextBoxPart();

        TextBoxSubmission::factory()->count(3)->create(['content_part_id' => $contentPart->id]);
        $otherSubmission = TextBoxSubmission::factory()->create(['content_part_id' => $otherContentPart->id]);

        $this->actingAs($user)
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
