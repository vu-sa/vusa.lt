<?php

use App\Actions\PairTranslatedRecord;
use App\Models\ContentEditorDraft;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Services\ContentEditorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::first();
    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->page = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
});

function editorDraftUrl(Page $page): string
{
    return route('api.v1.admin.contentEditor.drafts.update', ['kind' => 'pages', 'identity' => 'record-'.$page->id]);
}

test('private recovery accepts incomplete forms without changing live content', function (): void {
    $title = $this->page->title;
    $this->actingAs($this->admin)->putJson(editorDraftUrl($this->page), ['revision' => 0, 'snapshot' => ['title' => '', 'content' => ['parts' => []]]])
        ->assertOk()->assertJsonPath('data.revision', 1)->assertJsonPath('data.snapshot.title', '');
    expect($this->page->fresh()->title)->toBe($title);
    $other = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->actingAs($other)->getJson(editorDraftUrl($this->page))->assertOk()->assertJsonPath('data', null);
});

test('recovery revisions reject stale writes and deletes', function (): void {
    $url = editorDraftUrl($this->page);
    $this->actingAs($this->admin)->putJson($url, ['revision' => 0, 'snapshot' => ['title' => 'First']])->assertOk();
    $this->putJson($url, ['revision' => 0, 'snapshot' => ['title' => 'Stale']])->assertStatus(409)->assertJsonPath('data.snapshot.title', 'First');
    $this->deleteJson($url, ['revision' => 0])->assertStatus(409);
    $this->deleteJson($url, ['revision' => 1])->assertOk();
    expect(ContentEditorDraft::count())->toBe(0);
});

test('recovery requires permission for the actual record', function (): void {
    $otherPage = Page::factory()->create();
    $this->actingAs($this->admin)->putJson(editorDraftUrl($otherPage), ['revision' => 0, 'snapshot' => []])->assertForbidden();
    expect(ContentEditorDraft::count())->toBe(0);
});

test('new recovery never creates a page', function (): void {
    $count = Page::count();
    $url = route('api.v1.admin.contentEditor.drafts.update', ['kind' => 'pages', 'identity' => 'new-00000000-0000-4000-8000-000000000001']);
    $this->actingAs($this->admin)->putJson($url, ['revision' => 0, 'snapshot' => ['title' => '', 'tenant_id' => $this->tenant->id]])->assertOk();
    expect(Page::count())->toBe($count);
});

test('manual saves persist page metadata and refuse stale record versions', function (): void {
    $snapshot = app(ContentEditorService::class)->snapshot($this->page);
    $url = route('api.v1.admin.contentEditor.pages.update', $this->page);
    $snapshot['meta_description'] = 'Description';
    $snapshot['highlights'] = ['One'];
    $snapshot['featured_image'] = '/uploads/cover.jpg';
    $this->actingAs($this->admin)->patchJson($url, $snapshot)->assertOk()->assertJsonPath('data.meta_description', 'Description');
    expect($this->page->fresh()->highlights)->toBe(['One']);
    $snapshot['title'] = 'Stale title';
    $this->patchJson($url, $snapshot)->assertStatus(409);
    expect($this->page->fresh()->title)->not->toBe('Stale title');
});

test('foreign block ids roll back metadata changes', function (): void {
    $other = Page::factory()->for($this->tenant)->create();
    $block = $other->content->parts()->create(['type' => 'tiptap', 'json_content' => ['type' => 'doc'], 'order' => 0]);
    $snapshot = app(ContentEditorService::class)->snapshot($this->page);
    $snapshot['title'] = 'Should roll back';
    $snapshot['content']['parts'] = [['id' => $block->id, 'type' => 'tiptap', 'json_content' => ['type' => 'doc']]];
    $this->actingAs($this->admin)->patchJson(route('api.v1.admin.contentEditor.pages.update', $this->page), $snapshot)->assertForbidden();
    expect($this->page->fresh()->title)->not->toBe('Should roll back');
});

test('language pairing checks language and counterpart authorization', function (): void {
    $target = Page::factory()->create(['lang' => 'en']);
    $snapshot = app(ContentEditorService::class)->snapshot($this->page);
    $snapshot['other_lang_id'] = $target->id;
    $this->actingAs($this->admin)->patchJson(route('api.v1.admin.contentEditor.pages.update', $this->page), $snapshot)->assertForbidden();
    $target->update(['tenant_id' => $this->tenant->id, 'lang' => 'lt']);
    $this->patchJson(route('api.v1.admin.contentEditor.pages.update', $this->page), $snapshot)->assertUnprocessable()->assertJsonValidationErrors('other_lang_id');
});

test('an unchanged pairing does not require edit rights over the counterpart', function (): void {
    $outside = Page::factory()->create(['lang' => 'en']);
    PairTranslatedRecord::execute($this->page, $outside->id);
    $snapshot = app(ContentEditorService::class)->snapshot($this->page->fresh());
    $snapshot['title'] = 'Still mine to edit';
    $this->actingAs($this->admin)->patchJson(route('api.v1.admin.contentEditor.pages.update', $this->page), $snapshot)->assertOk();
    expect($this->page->fresh()->title)->toBe('Still mine to edit')
        ->and($this->page->fresh()->other_lang_id)->toBe($outside->id);
});

test('saving keeps whitespace-only and edge whitespace in text nodes', function (): void {
    $snapshot = app(ContentEditorService::class)->snapshot($this->page);
    $snapshot['content']['parts'] = [['key' => 'spaced', 'type' => 'tiptap', 'json_content' => ['type' => 'doc', 'content' => [[
        'type' => 'paragraph',
        'content' => [['type' => 'text', 'text' => 'Bold ', 'marks' => [['type' => 'bold']]], ['type' => 'text', 'text' => ' '], ['type' => 'text', 'text' => 'tail']],
    ]]]]];
    $this->actingAs($this->admin)->patchJson(route('api.v1.admin.contentEditor.pages.update', $this->page), $snapshot)->assertOk();
    $nodes = $this->page->fresh()->content->parts->first()->json_content['content'][0]['content'];
    expect($nodes[0]['text'])->toBe('Bold ')->and($nodes[1]['text'])->toBe(' ');
});

test('recovery retention keeps recently edited copies', function (): void {
    $attributes = ['user_id' => $this->admin->id, 'kind' => 'pages', 'snapshot' => [], 'revision' => 1];
    ContentEditorDraft::create([...$attributes, 'identity' => 'new-old'])->forceFill(['updated_at' => now()->subDays(31)])->save();
    ContentEditorDraft::create([...$attributes, 'identity' => 'new-current']);
    $this->artisan('model:prune', ['--model' => [ContentEditorDraft::class]])->assertSuccessful();
    expect(ContentEditorDraft::count())->toBe(1);
});

test('pair replacement requires a current confirmation and releases both old partners', function (): void {
    $old = Page::factory()->for($this->tenant)->create(['lang' => 'en']);
    $target = Page::factory()->for($this->tenant)->create(['lang' => 'en']);
    $previous = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
    PairTranslatedRecord::execute($this->page, $old->id);
    PairTranslatedRecord::execute($target, $previous->id);
    $snapshot = app(ContentEditorService::class)->snapshot($this->page->fresh());
    $snapshot['other_lang_id'] = $target->id;
    $url = route('api.v1.admin.contentEditor.pages.update', $this->page);
    $previewUrl = route('api.v1.admin.contentEditor.pairing', ['kind' => 'pages']);
    $selection = ['record_id' => $this->page->id, 'target_id' => $target->id, 'lang' => 'lt'];
    $this->actingAs($this->admin)->patchJson($url, $snapshot)->assertUnprocessable()->assertJsonValidationErrors('other_lang_id');
    $preview = $this->postJson($previewUrl, $selection)->assertOk()->assertJsonPath('data.confirmation_required', true);
    $snapshot['pairing_confirmation'] = $preview->json('data.token');
    $target->update(['title' => 'Renamed counterpart']);
    $this->patchJson($url, $snapshot)->assertUnprocessable();
    $snapshot['pairing_confirmation'] = $this->postJson($previewUrl, $selection)->assertOk()->json('data.token');
    $this->patchJson($url, $snapshot)->assertOk();
    expect($this->page->fresh()->other_lang_id)->toBe($target->id)
        ->and($target->fresh()->other_lang_id)->toBe($this->page->id)
        ->and($old->fresh()->other_lang_id)->toBeNull()
        ->and($previous->fresh()->other_lang_id)->toBeNull();
});

test('pair replacement authorizes the previous partner as well as the selected record', function (): void {
    $target = Page::factory()->for($this->tenant)->create(['lang' => 'en']);
    $outside = Page::factory()->create(['lang' => 'lt']);
    PairTranslatedRecord::execute($target, $outside->id);
    $this->actingAs($this->admin)->postJson(route('api.v1.admin.contentEditor.pairing', ['kind' => 'pages']), [
        'record_id' => $this->page->id, 'target_id' => $target->id, 'lang' => 'lt',
    ])->assertForbidden();
    expect($target->fresh()->other_lang_id)->toBe($outside->id);
});

test('a new language version saves separate blocks and remains unpublished', function (): void {
    $snapshot = app(ContentEditorService::class)->snapshot($this->page);
    unset($snapshot['id'], $snapshot['content_version'], $snapshot['permalink']);
    $snapshot['lang'] = 'en';
    $snapshot['is_active'] = false;
    $snapshot['other_lang_id'] = $this->page->id;
    foreach ($snapshot['content']['parts'] as &$part) {
        unset($part['id']);
        $part['key'] = 'new-translation-block';
    }
    unset($part);
    $response = $this->actingAs($this->admin)->postJson(route('api.v1.admin.contentEditor.pages.store'), $snapshot)
        ->assertOk()->assertJsonPath('data.is_active', false)->assertJsonPath('data.other_lang_id', $this->page->id);
    $translated = Page::findOrFail($response->json('data.id'));
    expect($this->page->fresh()->other_lang_id)->toBe($translated->id)
        ->and($translated->content_id)->not->toBe($this->page->content_id)
        ->and($translated->content->parts->first()->id)->not->toBe($this->page->content->parts->first()->id);
});

test('summary limits count visible unicode characters and preserve unchanged legacy introductions', function (): void {
    $news = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'short' => '<p>'.str_repeat('ą', 201).'</p>']);
    $snapshot = app(ContentEditorService::class)->snapshot($news->fresh());
    $url = route('api.v1.admin.contentEditor.news.update', $news);
    $snapshot['title'] = 'Changed title';
    $this->actingAs($this->admin)->patchJson($url, $snapshot)->assertOk();
    $snapshot = app(ContentEditorService::class)->snapshot($news->fresh());
    $snapshot['short'] = '<p><strong>'.str_repeat('ą', 199).'</strong>&amp;</p>';
    $this->patchJson($url, $snapshot)->assertOk();
    $snapshot = app(ContentEditorService::class)->snapshot($news->fresh());
    $snapshot['short'] = '<p>'.str_repeat('ą', 200).'&amp;</p>';
    $this->patchJson($url, $snapshot)->assertUnprocessable()->assertJsonValidationErrors('short');
});

test('news publication dates round trip without timezone shifts and reject invalid input', function (): void {
    $news = News::factory()->for($this->tenant)->create(['lang' => 'lt', 'publish_time' => now()->startOfSecond()]);
    $snapshot = app(ContentEditorService::class)->snapshot($news->fresh());
    $url = route('api.v1.admin.contentEditor.news.update', $news);
    $time = $news->publish_time->timestamp;
    $this->actingAs($this->admin)->patchJson($url, $snapshot)->assertOk();
    expect($news->fresh()->publish_time->timestamp)->toBe($time);
    $snapshot = app(ContentEditorService::class)->snapshot($news->fresh());
    $snapshot['publish_time'] = 'invalid date';
    $this->patchJson($url, $snapshot)->assertUnprocessable()->assertJsonValidationErrors('publish_time');
    expect($news->fresh()->publish_time->timestamp)->toBe($time);
});

test('heading links remain unique across blocks and match server rendered html', function (): void {
    $heading = ['type' => 'heading', 'attrs' => ['level' => 2, 'id' => 'stable'], 'content' => [
        ['type' => 'text', 'text' => 'Įvadas '], ['type' => 'text', 'text' => 'ir informacija', 'marks' => [['type' => 'bold']]],
    ]];
    $snapshot = app(ContentEditorService::class)->snapshot($this->page);
    $snapshot['content']['parts'] = [
        ['key' => 'first', 'type' => 'tiptap', 'json_content' => ['type' => 'doc', 'content' => [$heading]]],
        ['key' => 'second', 'type' => 'tiptap', 'json_content' => ['type' => 'doc', 'content' => [$heading]]],
    ];
    $this->actingAs($this->admin)->patchJson(route('api.v1.admin.contentEditor.pages.update', $this->page), $snapshot)
        ->assertOk()->assertJsonPath('data.content.parts.0.json_content.content.0.attrs.id', 'stable')
        ->assertJsonPath('data.content.parts.1.json_content.content.0.attrs.id', 'stable-1');
    $parts = $this->page->fresh()->content->parts;
    expect($parts[0]->html)->toContain('id="stable"')
        ->and($parts[1]->html)->toContain('id="stable-1"');
});
