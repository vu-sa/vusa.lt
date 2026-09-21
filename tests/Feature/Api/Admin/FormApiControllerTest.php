<?php

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->first() ?? Tenant::factory()->create();

    $this->plainUser = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->form = Form::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Testinė forma', 'en' => 'Test form'],
    ]);
});

test('returns 403 to a user without form read access', function (): void {
    asUser($this->plainUser)
        ->getJson(route('api.v1.admin.forms.index'))
        ->assertForbidden();
});

test('returns paginated form collection contract for authorized user', function (): void {
    asUser($this->admin)
        ->getJson(route('api.v1.admin.forms.index', ['per_page' => 1]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonStructure(['data' => ['items', 'total', 'per_page', 'current_page', 'last_page']]);
});

test('filters forms by search query', function (): void {
    Form::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Ypatinga apklausa', 'en' => 'Special survey'],
    ]);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.forms.index', ['search' => 'Ypatinga']))
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.name.lt', 'Ypatinga apklausa');
});
