<?php

use App\Actions\ActivityRequests\GetUserActivityRequests;
use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->freezeTime();
    $this->tenant = Tenant::query()->firstOrFail();
    $this->recipient = makeUser($this->tenant);
    $this->institution = $this->recipient->duties()->firstOrFail()->institution;
    $this->history = app(GetUserActivityRequests::class);
});

test('personal history requires authentication', function (): void {
    $this->get(route('profile.activityRequests'))->assertRedirect(route('login'));
});

test('personal history stays self scoped without any administrative permission or current duty', function (): void {
    $this->recipient->dutiables()->update(['end_date' => today()->subDay()]);
    $own = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);
    InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id]);

    asUser($this->recipient)->get(route('profile.activityRequests', ['recipient_id' => User::factory()->create()->id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/ShowMyActivityRequests')
        ->has('activityRequests.data', 1)
        ->where('activityRequests.data.0.id', $own->id)
        ->where('activityRequests.data.0.status', 'pending')
        ->where('activityRequests.data.0.answer_url', $own->answerUrl()));
});

test('user record history is deferred and restricted per institution before pagination', function (): void {
    $viewer = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
    $visible = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);
    $foreign = Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail())->create();
    InstitutionActivityRequest::factory()->count(21)->create(['recipient_id' => $this->recipient->id, 'institution_id' => $foreign->id]);

    asUser($viewer)->get(route('users.show', $this->recipient))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.viewActivityRequests', true)->missing('activityRequests')
            ->loadDeferredProps('activityRequests', fn (Assert $reload) => $reload
                ->has('activityRequests.data', 1)
                ->where('activityRequests.data.0.id', $visible->id)
                ->where('activityRequests.data.0.answer_url', null)
                ->where('activityRequests.next_page', null)));
});

test('reading a user alone gives no request history even on a partial reload', function (): void {
    $viewer = makeUser($this->tenant);
    $role = Role::create(['name' => 'Test user reader', 'guard_name' => 'web']);
    $role->givePermissionTo('users.read.padalinys');
    $viewer->duties()->firstOrFail()->assignRole($role);
    InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);

    asUser($viewer)->get(route('users.show', $this->recipient))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('can.viewActivityRequests', false)
            ->loadDeferredProps('activityRequests', fn (Assert $reload) => $reload->has('activityRequests.data', 0)));
});

test('a person without user read access cannot open another user record', function (): void {
    asUser($this->recipient)->get(route('users.show', User::factory()->create()))->assertForbidden();
});

test('institution specific coordination reveals only that institutions requests', function (): void {
    $viewer = makeUser($this->tenant);
    ResponsibilityGrant::factory()->forDuty($viewer->duties()->firstOrFail())->forInstitution($this->institution)->create();
    $visible = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);
    InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => Institution::factory()->for($this->tenant)]);

    $history = $this->history->history($this->recipient, $viewer);

    expect(array_column($history['data'], 'id'))->toBe([$visible->id]);
    expect($history['data'][0]['answer_url'])->toBeNull();
});

test('global institution editors and super administrators see requests across tenants', function (string $role): void {
    $viewer = $role === config('permission.super_admin_role_name')
        ? makeAdminUser($this->tenant) : makeTenantUserWithRole($role, $this->tenant);
    InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);
    InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id]);

    $history = $this->history->history($this->recipient, $viewer);

    expect($history['data'])->toHaveCount(2);
    expect(array_column($history['data'], 'answer_url'))->toBe([null, null]);
})->with(['Centrinio biuro studentų atstovų koordinatorius', 'Super Admin']);

test('history preserves both campaign types and status precedence', function (array $state, string $expected): void {
    $request = InstitutionActivityRequest::factory()->create([
        'recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id,
        'campaign_type' => InstitutionActivityCampaign::MissingMeetings, ...$state,
    ]);

    $row = $this->history->history($this->recipient, $this->recipient)['data'][0];

    expect($row['status'])->toBe($expected);
    expect($row['campaign_type'])->toBe('missing_meetings');
    expect($row['answer_url'])->toBe($expected === 'pending' ? $request->answerUrl() : null);
})->with([
    'pending' => [[], 'pending'],
    'answered wins' => [fn () => ['answer' => InstitutionActivityAnswer::NotMet, 'answered_at' => now(), 'resolved_at' => now(), 'expires_at' => today()->subDay()], 'answered'],
    'resolved wins' => [fn () => ['resolved_at' => now(), 'expires_at' => today()->subDay()], 'resolved'],
    'expired' => [fn () => ['expires_at' => today()->subDay()], 'expired'],
]);

test('history puts pending requests first and paginates twenty at a time', function (): void {
    InstitutionActivityRequest::factory()->count(20)->answered()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);
    $pending = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id, 'created_at' => today()->subDay()]);

    $first = $this->history->history($this->recipient, $this->recipient);
    $second = $this->history->history($this->recipient, $this->recipient, 2);

    expect($first['data'])->toHaveCount(20);
    expect($first['data'][0]['id'])->toBe($pending->id);
    expect($first['next_page'])->toBe(2);
    expect($second['data'])->toHaveCount(1);
    expect($second['page'])->toBe(2);
    expect($second['next_page'])->toBeNull();
});

test('home initially shows only three own pending requests by expiry with their total', function (): void {
    $first = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id, 'expires_at' => today()->addDay()]);
    InstitutionActivityRequest::factory()->count(3)->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);
    InstitutionActivityRequest::factory()->answered()->create(['recipient_id' => $this->recipient->id]);
    InstitutionActivityRequest::factory()->expired()->create(['recipient_id' => $this->recipient->id]);
    InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'resolved_at' => now()]);
    InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id]);
    $deleted = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id]);
    $deleted->institution->delete();

    asUser($this->recipient)->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('activeActivityRequests.total', 4)->has('activeActivityRequests.data', 3)
        ->where('activeActivityRequests.data.0.id', $first->id)
        ->where('activeActivityRequests.data.0.answer_url', $first->answerUrl()));
});

test('recipient answer links retain the original expiry and opening one records nothing', function (): void {
    $request = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'institution_id' => $this->institution->id]);
    $url = $this->history->history($this->recipient, $this->recipient)['data'][0]['answer_url'];

    expect(URL::hasValidSignature(Request::create($url)))->toBeTrue();
    asUser($this->recipient)->get($url)->assertOk();
    expect($request->fresh()->answered_at)->toBeNull();
    $this->travelTo($request->expires_at->addSecond());
    $this->get($url)->assertForbidden();
});
