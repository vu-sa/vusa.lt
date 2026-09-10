<?php

use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Models\Duty;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\SupportRequestArea;
use App\Models\SupportRequestType;
use App\Models\SupportService;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);

    $this->service = SupportService::factory()->create();
    $this->type = SupportRequestType::factory()->create();
    $this->area = SupportRequestArea::factory()->create(['support_service_id' => $this->service->id]);
});

describe('guest access', function (): void {
    test('guest is redirected to login from my-support-requests index', function (): void {
        $this->get(route('mySupportRequests.index'))->assertRedirect(route('login'));
    });

    test('guest is redirected to login from create page', function (): void {
        $this->get(route('mySupportRequests.create'))->assertRedirect(route('login'));
    });

    test('guest is redirected to login from store', function (): void {
        $this->post(route('mySupportRequests.store'), [])->assertRedirect(route('login'));
    });
});

describe('authenticated user index', function (): void {
    test('shows all visible support requests by default and mine on request', function (): void {
        $ownActive = SupportRequest::factory()->create([
            'created_by' => $this->user->id,
            'status' => SupportRequestStatus::New,
        ]);

        $ownResolved = SupportRequest::factory()->done()->create([
            'created_by' => $this->user->id,
        ]);

        $otherPrivate = SupportRequest::factory()->create([
            'created_by' => makeUser($this->tenant)->id,
            'visibility' => SupportRequestVisibility::Private,
        ]);

        $publicRequest = SupportRequest::factory()->create([
            'created_by' => makeUser($this->tenant)->id,
            'visibility' => SupportRequestVisibility::Public,
        ]);

        asUser($this->user)->get(route('mySupportRequests.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard/ShowSupportRequests')
                ->where('currentTab', 'all')
                ->where('tabCounts.all', 3)
                ->where('tabCounts.mine', 2)
                ->has('requests.data', 3)
                ->where('requests.data', fn ($requests) => collect($requests)->pluck('id')->contains($publicRequest->id)
                    && ! collect($requests)->pluck('id')->contains($otherPrivate->id))
            );

        asUser($this->user)->get(route('mySupportRequests.index', ['tab' => 'mine']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('currentTab', 'mine')
                ->has('requests.data', 2)
            );
    });

    test('shows requests shared with one of the user’s roles on the all dashboard', function (): void {
        $role = Role::create(['name' => 'Support reviewers', 'guard_name' => 'web']);
        $this->user->roles()->attach($role->id);

        $sharedRequest = SupportRequest::factory()->create([
            'created_by' => makeUser($this->tenant)->id,
            'visibility' => SupportRequestVisibility::Roles,
        ]);
        $sharedRequest->roles()->attach($role->id);

        asUser($this->user)->get(route('mySupportRequests.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('tabCounts.all', 1)
                ->where('tabCounts.mine', 0)
                ->where('requests.data', fn ($requests) => collect($requests)->pluck('id')->contains($sharedRequest->id))
            );
    });
});

describe('creating and storing support requests', function (): void {
    test('can access create page with taxonomies and roles localized by language', function (): void {
        app()->setLocale('lt');
        asUser($this->user)->get(route('mySupportRequests.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SupportRequests/CreateSupportRequest')
                ->has('types')
                ->has('areas')
                ->has('roles')
                ->where('types', fn ($types) => collect($types)->contains('name', $this->type->getTranslation('name', 'lt')))
                ->where('areas', fn ($areas) => collect($areas)->contains('name', $this->area->getTranslation('name', 'lt')))
            );

        app()->setLocale('en');
        asUser($this->user)->get(route('mySupportRequests.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('types', fn ($types) => collect($types)->contains('name', $this->type->getTranslation('name', 'en')))
                ->where('areas', fn ($areas) => collect($areas)->contains('name', $this->area->getTranslation('name', 'en')))
            );
    });

    test('create page roles only include current users through duties and not past users', function (): void {
        $role = Role::create(['name' => 'Coordinator Role', 'guard_name' => 'web']);
        $duty = Duty::factory()->create();
        $duty->roles()->attach($role->id);

        $duty->users()->attach($this->user->id, [
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
        ]);

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

        asUser($this->user)->get(route('mySupportRequests.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('roles', function ($roles) use ($role, $currentUser, $pastUser) {
                    $foundRole = collect($roles)->firstWhere('id', $role->id);
                    expect($foundRole)->not->toBeNull();

                    $userIds = collect($foundRole['users'])->pluck('id');

                    return $userIds->contains($currentUser->id) && ! $userIds->contains($pastUser->id);
                })
            );
    });

    test('user cannot select roles they do not have', function (): void {
        $otherRole = Role::create(['name' => 'Foreign Role', 'guard_name' => 'web']);

        asUser($this->user)->get(route('mySupportRequests.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('roles', fn ($roles) => collect($roles)->firstWhere('id', $otherRole->id) === null)
            );

        asUser($this->user)->post(route('mySupportRequests.store'), [
            'title' => 'Puslapio klaida formoje',
            'description' => 'Nepavyksta paspausti mygtuko dėl JS klaidos',
            'support_service_id' => $this->service->id,
            'support_request_type_id' => $this->type->id,
            'support_request_area_id' => $this->area->id,
            'visibility' => 'roles',
            'roles' => [$otherRole->id],
        ])->assertSessionHasErrors('roles.0');
    });

    test('can store a support request with role visibility and media attachments', function (): void {
        Storage::fake('spatieMediaLibrary');

        $role = Role::create(['name' => 'Support Reviewer', 'guard_name' => 'web']);
        $this->user->roles()->attach($role->id);
        $file = UploadedFile::fake()->image('screenshot.png', 800, 600);

        $payload = [
            'title' => 'Puslapio klaida formoje',
            'description' => 'Nepavyksta paspausti mygtuko dėl JS klaidos',
            'support_service_id' => $this->service->id,
            'support_request_type_id' => $this->type->id,
            'support_request_area_id' => $this->area->id,
            'visibility' => 'roles',
            'roles' => [$role->id],
            'context_url' => 'http://www.vusa.test/lt/forma',
            'images' => [$file],
        ];

        $response = asUser($this->user)->post(route('mySupportRequests.store'), $payload);

        $supportRequest = SupportRequest::where('created_by', $this->user->id)->latest()->first();
        expect($supportRequest)->not->toBeNull()
            ->and($supportRequest->title)->toBe('Puslapio klaida formoje')
            ->and($supportRequest->visibility)->toBe(SupportRequestVisibility::Roles)
            ->and($supportRequest->roles()->pluck('roles.id')->all())->toContain($role->id);

        $response->assertRedirect(route('supportRequests.show', $supportRequest->id));

        // Check media attachment
        expect($supportRequest->getMedia('evidence'))->toHaveCount(1);
    });

    test('does not accept selected text from the admin create form', function (): void {
        asUser($this->user)->post(route('mySupportRequests.store'), [
            'title' => 'Puslapio klaida formoje',
            'description' => 'Nepavyksta paspausti mygtuko dėl JS klaidos',
            'support_request_type_id' => $this->type->id,
            'support_request_area_id' => $this->area->id,
            'visibility' => 'private',
            'selected_text' => 'Šis tekstas skirtas tik viešam atsiliepimui.',
        ])->assertRedirect();

        expect(SupportRequest::query()->latest()->first()?->selected_text)->toBeNull();
    });
});

describe('creator editing permissions', function (): void {
    test('creator can edit new support request', function (): void {
        $request = SupportRequest::factory()->create([
            'created_by' => $this->user->id,
            'status' => SupportRequestStatus::New,
            'support_service_id' => $this->service->id,
            'support_request_type_id' => $this->type->id,
            'support_request_area_id' => $this->area->id,
        ]);

        asUser($this->user)->get(route('supportRequests.edit', $request->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SupportRequests/EditSupportRequest')
                ->where('supportRequest.id', $request->id)
            );

        asUser($this->user)->put(route('supportRequests.update', $request->id), [
            'title' => 'Atnaujintas pavadinimas',
            'description' => 'Atnaujintas aprašymas',
            'support_service_id' => $this->service->id,
            'support_request_type_id' => $this->type->id,
            'support_request_area_id' => $this->area->id,
            'visibility' => 'private',
        ])->assertRedirect(route('supportRequests.show', $request->id));

        expect($request->fresh()->title)->toBe('Atnaujintas pavadinimas');
    });

    test('creator cannot edit support request that is already in progress or done', function (): void {
        $request = SupportRequest::factory()->create([
            'created_by' => $this->user->id,
            'status' => SupportRequestStatus::InProgress,
        ]);

        asUser($this->user)->get(route('supportRequests.edit', $request->id))->assertStatus(403);

        asUser($this->user)->put(route('supportRequests.update', $request->id), [
            'title' => 'Bandyta pakeisti',
        ])->assertStatus(403);
    });

    test('does not overwrite public selected text from the admin edit form', function (): void {
        $request = SupportRequest::factory()->create([
            'created_by' => $this->user->id,
            'status' => SupportRequestStatus::New,
            'selected_text' => 'Viešame puslapyje pažymėtas tekstas',
        ]);

        asUser($this->user)->put(route('supportRequests.update', $request->id), [
            'title' => 'Atnaujintas pavadinimas',
            'description' => 'Atnaujintas aprašymas',
            'support_request_type_id' => $request->support_request_type_id,
            'support_request_area_id' => $request->support_request_area_id,
            'visibility' => 'private',
            'selected_text' => 'Bandyta perrašyti',
        ])->assertRedirect();

        expect($request->fresh()->selected_text)->toBe('Viešame puslapyje pažymėtas tekstas');
    });
});
