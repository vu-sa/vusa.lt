<?php

use App\Models\ContentPart;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\TextBoxSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

function makeTextBoxContentPart(?Tenant $tenant = null): ContentPart
{
    $page = Page::factory()->for($tenant ?? Tenant::query()->first())->create();

    return ContentPart::factory()->create([
        'content_id' => $page->content_id,
        'type' => 'text-box',
        'json_content' => [],
        'options' => [
            'title' => ['lt' => 'Klausimas', 'en' => 'Question'],
            'placeholder' => ['lt' => 'Atsakykite...', 'en' => 'Answer...'],
        ],
    ]);
}

function makeTiptapContentPart(): ContentPart
{
    $page = Page::factory()->for(Tenant::query()->first())->create();

    return ContentPart::factory()->create([
        'content_id' => $page->content_id,
        'type' => 'tiptap',
        'json_content' => [],
    ]);
}

/**
 * A user permitted to view/manage the page that owns text-box submissions.
 */
function makeSubmissionsManager(): User
{
    return makeTenantUserWithRole('Communication Coordinator', Tenant::query()->first());
}

// Public store endpoint

it('can submit to a text-box content part', function () {
    $contentPart = makeTextBoxContentPart();

    $response = $this->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => $contentPart->id,
        'text' => 'This is my feedback.',
    ]);

    $response->assertCreated();
    $response->assertJson(['success' => true]);

    expect(TextBoxSubmission::count())->toBe(1);
    expect(TextBoxSubmission::first()->text)->toBe('This is my feedback.');
});

it('rejects submission to a non-text-box content part', function () {
    $contentPart = makeTiptapContentPart();

    $response = $this->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => $contentPart->id,
        'text' => 'Some text.',
    ]);

    $response->assertUnprocessable();
    expect(TextBoxSubmission::count())->toBe(0);
});

it('requires the text field', function () {
    $contentPart = makeTextBoxContentPart();

    $response = $this->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => $contentPart->id,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['text']);
});

it('rejects text shorter than 10 characters', function () {
    $contentPart = makeTextBoxContentPart();

    $response = $this->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => $contentPart->id,
        'text' => 'Short',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['text']);
    expect(TextBoxSubmission::count())->toBe(0);
});

it('silently succeeds when the honeypot field is filled', function () {
    $contentPart = makeTextBoxContentPart();

    $response = $this->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => $contentPart->id,
        'text' => 'This is bot spam content here.',
        'website' => 'http://spam.example.com',
    ]);

    $response->assertCreated();
    $response->assertJson(['success' => true]);
    // No real submission was stored
    expect(TextBoxSubmission::count())->toBe(0);
});

it('requires a valid content_part_id', function () {
    $response = $this->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => 99999,
        'text' => 'Some text.',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['content_part_id']);
});

it('associates the submission with the logged-in user', function () {
    $user = User::factory()->create();
    $contentPart = makeTextBoxContentPart();

    $this->actingAs($user)->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => $contentPart->id,
        'text' => 'Authenticated submission.',
    ]);

    $submission = TextBoxSubmission::first();
    expect($submission->user_id)->toBe($user->id);
});

it('stores a null user_id for guests', function () {
    $contentPart = makeTextBoxContentPart();

    $this->postJson(route('api.v1.text-box-submissions.store'), [
        'content_part_id' => $contentPart->id,
        'text' => 'Guest submission.',
    ]);

    $submission = TextBoxSubmission::first();
    expect($submission->user_id)->toBeNull();
});

// Admin index endpoint

it('requires auth for the admin submissions endpoint', function () {
    $contentPart = makeTextBoxContentPart();

    $response = $this->getJson(route('api.v1.admin.text-box-submissions.index', [
        'content_part_id' => $contentPart->id,
    ]));

    $response->assertUnauthorized();
});

it('returns submissions for a content part', function () {
    $user = makeSubmissionsManager();
    $contentPart = makeTextBoxContentPart();

    TextBoxSubmission::factory()->count(3)->create([
        'content_part_id' => $contentPart->id,
    ]);

    $response = $this->actingAs($user)->getJson(route('api.v1.admin.text-box-submissions.index', [
        'content_part_id' => $contentPart->id,
    ]));

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect($response->json('data'))->toHaveCount(3);
});

it('returns submissions ordered by newest first', function () {
    $user = makeSubmissionsManager();
    $contentPart = makeTextBoxContentPart();

    $first = TextBoxSubmission::factory()->create([
        'content_part_id' => $contentPart->id,
        'text' => 'first',
        'created_at' => now()->subMinutes(5),
    ]);

    $second = TextBoxSubmission::factory()->create([
        'content_part_id' => $contentPart->id,
        'text' => 'second',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->getJson(route('api.v1.admin.text-box-submissions.index', [
        'content_part_id' => $contentPart->id,
    ]));

    $data = $response->json('data');
    expect($data[0]['text'])->toBe('second');
    expect($data[1]['text'])->toBe('first');
});

// Admin export endpoint

it('exports submissions as an xlsx file', function () {
    $user = makeSubmissionsManager();
    $contentPart = makeTextBoxContentPart();

    TextBoxSubmission::factory()->create([
        'content_part_id' => $contentPart->id,
        'text' => 'First answer here.',
        'user_id' => $user->id,
    ]);
    TextBoxSubmission::factory()->create([
        'content_part_id' => $contentPart->id,
        'text' => 'Anonymous answer.',
        'user_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('api.v1.admin.text-box-submissions.export', [
        'content_part_id' => $contentPart->id,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $body = $response->streamedContent();
    $tmp = tempnam(sys_get_temp_dir(), 'export_').'.xlsx';
    file_put_contents($tmp, $body);

    $loaded = IOFactory::load($tmp);
    $rows = $loaded->getActiveSheet()->toArray(null, true, true, false);
    unlink($tmp);

    expect($rows[0])->toBe(['Response', 'Submitted by', 'Submitted at']);
    expect($rows)->toHaveCount(3);
    $textColumn = array_column(array_slice($rows, 1), 0);
    expect($textColumn)->toContain('First answer here.', 'Anonymous answer.');
});

it('shows Anonymous for submissions without a user', function () {
    $user = makeSubmissionsManager();
    $contentPart = makeTextBoxContentPart();

    TextBoxSubmission::factory()->create([
        'content_part_id' => $contentPart->id,
        'user_id' => null,
    ]);

    $response = $this->actingAs($user)->getJson(route('api.v1.admin.text-box-submissions.index', [
        'content_part_id' => $contentPart->id,
    ]));

    expect($response->json('data.0.submitted_by'))->toBe('Anonymous');
});
