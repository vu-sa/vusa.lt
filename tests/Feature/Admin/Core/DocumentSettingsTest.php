<?php

use App\Models\Document;
use App\Models\Tenant;
use App\Services\Typesense\DocumentRecommendations;
use App\Settings\DocumentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = makeUser(Tenant::query()->first());
    $this->admin->assignRole(config('permission.super_admin_role_name'));
});

test('settings managers save recommendations and receive their selected document titles', function (): void {
    $document = Document::factory()->create(['title' => 'VU SA Įstatai', 'is_active' => true]);
    $rule = ['document_id' => (string) $document->id, 'phrases' => [' įstatai ', 'įstatai', 'VU SA įstatai'], 'enabled' => true, 'show_without_query' => false];
    asUser($this->admin)->post(route('settings.documents.update'), ['important_content_types' => ['Įstatai'], 'recommendations' => [$rule]])->assertRedirect()->assertSessionHasNoErrors();
    expect(app(DocumentSettings::class)->recommendations)->toBe([[...$rule, 'phrases' => ['įstatai', 'VU SA įstatai']]]);
    asUser($this->admin)->get(route('settings.documents.edit'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Settings/EditDocumentSettings')->where('selected_documents.0.title', 'VU SA Įstatai')->where('recommendations.0.document_id', (string) $document->id));
});

test('saving recommendations makes their phrases searchable right away', function (): void {
    usesTypesense();
    $document = Document::factory()->create(['title' => 'VU SA Įstatai', 'language' => 'Lietuvių', 'is_active' => true]);
    $rule = ['document_id' => (string) $document->id, 'phrases' => ['VU SA įstatai'], 'enabled' => true, 'show_without_query' => false];
    asUser($this->admin)->post(route('settings.documents.update'), ['recommendations' => [$rule]])->assertSessionHasNoErrors();
    expect(app(DocumentRecommendations::class)->matchingIds('įstat'))->toBe([(string) $document->id]);
});

test('members without settings access cannot read or change recommendations', function (): void {
    $member = makeUser(Tenant::query()->first());
    asUser($member)->get(route('settings.documents.edit'))->assertForbidden();
    asUser($member)->post(route('settings.documents.update'), ['recommendations' => []])->assertForbidden();
});

test('recommendations require an active document and an enabled trigger', function (): void {
    $document = Document::factory()->create(['is_active' => true]);
    $rule = ['document_id' => (string) $document->id, 'phrases' => [], 'enabled' => true, 'show_without_query' => false];
    asUser($this->admin)->post(route('settings.documents.update'), ['recommendations' => [$rule]])->assertSessionHasErrors('recommendations.0.phrases');
    asUser($this->admin)->post(route('settings.documents.update'), ['recommendations' => [[...$rule, 'show_without_query' => true]]])->assertSessionHasNoErrors();
    asUser($this->admin)->post(route('settings.documents.update'), ['recommendations' => [[...$rule, 'enabled' => false]]])->assertSessionHasNoErrors();
    $rule['phrases'] = ['įstatai'];
    asUser($this->admin)->post(route('settings.documents.update'), ['recommendations' => [$rule, $rule]])->assertSessionHasErrors('recommendations.0.document_id');
    $document->update(['is_active' => false]);
    asUser($this->admin)->post(route('settings.documents.update'), ['recommendations' => [$rule]])->assertSessionHasErrors('recommendations.0.document_id');
    $document->delete();
    asUser($this->admin)->post(route('settings.documents.update'), ['recommendations' => [$rule]])->assertSessionHasErrors('recommendations.0.document_id');
});
