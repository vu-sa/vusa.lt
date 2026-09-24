<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);
});

describe('unauthorized access', function (): void {
    test('a simple user cannot index types', function (): void {
        asUser($this->user)->get(route('types.index'))->assertStatus(403);
    });

    test('a simple user cannot store a type', function (): void {
        asUser($this->user)->post(route('types.store'), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => MorphMap::alias(Duty::class),
        ])->assertStatus(403);
    });
});

describe('model_type allowlist', function (): void {
    /**
     * `model_type` used to be turned into a method name and invoked on the model
     * (`$type->$modelType()->sync(...)`). Anything outside the allowlist must now
     * be a validation error — never a dynamic dispatch, and never a 500.
     */
    test('rejects a model_type outside the allowlist when storing', function (string $modelType): void {
        asUser($this->admin)->post(route('types.store'), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => $modelType,
        ])->assertSessionHasErrors('model_type');

        expect(Type::query()->where('model_type', $modelType)->exists())->toBeFalse();
    })->with([
        'roles relation' => [Role::class],
        'a relation that would 500' => ['App\Models\Descendant'],
        'an arbitrary class' => [User::class],
        'not a class at all' => ['nonsense'],
        'empty string' => [''],
    ]);

    test('rejects a model_type outside the allowlist when updating', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Duty::class)]);

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => MorphMap::alias(Role::class),
        ])->assertSessionHasErrors('model_type');

        expect($type->fresh()->model_type)->toBe(MorphMap::alias(Duty::class));
    });

    test('a bogus model_type cannot sync roles onto a type', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);
        $role = Role::query()->first();

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => MorphMap::alias(Role::class),
            'roles' => [$role->id],
        ])->assertSessionHasErrors('model_type');

        expect($type->fresh()->roles)->toBeEmpty();
    });
});

describe('allowed model types still work', function (): void {
    test('record page shows assigned models and defers picker options', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);
        $institution = Institution::factory()->for($this->tenant)->create();
        $type->institutions()->attach($institution);

        asUser($this->admin)->get(route('types.show', $type))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ModelMeta/ShowType')
                ->where('attachedModels.0.id', $institution->id)
                ->missing('modelOptions')
                ->missing('roleOptions')
            );
    });

    test('can store an institution type', function (): void {
        $response = asUser($this->admin)->post(route('types.store'), [
            'title' => ['lt' => 'Padalinys', 'en' => 'Unit'],
            'model_type' => MorphMap::alias(Institution::class),
        ]);

        $type = Type::query()->where('model_type', MorphMap::alias(Institution::class))
            ->where('title->lt', 'Padalinys')->firstOrFail();
        $response->assertRedirect(route('types.show', $type));
    });

    test('saving attributes leaves institution assignments alone', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);
        $institution = Institution::factory()->for($this->tenant)->create();
        $type->institutions()->attach($institution);

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Padalinys', 'en' => 'Unit'],
            'model_type' => MorphMap::alias(Institution::class),
        ])->assertRedirect();

        expect($type->fresh()->institutions->pluck('id'))->toContain($institution->id);
    });

    test('syncs institutions from the record page', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);
        $institution = Institution::factory()->for($this->tenant)->create();

        asUser($this->admin)->put(route('types.models.sync', $type), [
            'models' => [$institution->id],
        ])->assertRedirect();

        expect($type->fresh()->institutions->pluck('id'))->toContain($institution->id);
    });

    test('syncs duties and roles from separate record actions', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Duty::class)]);
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $role = Role::query()->first();

        asUser($this->admin)->put(route('types.models.sync', $type), [
            'models' => [$duty->id],
        ])->assertRedirect();
        asUser($this->admin)->put(route('types.roles.sync', $type), [
            'roles' => [$role->id],
        ])->assertRedirect();

        expect($type->fresh()->duties->pluck('id'))->toContain($duty->id)
            ->and($type->fresh()->roles->pluck('id'))->toContain($role->id);
    });

    test('rejects a model id from the wrong relation', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();

        asUser($this->admin)->put(route('types.models.sync', $type), [
            'models' => [$duty->id],
        ])->assertSessionHasErrors('models.0');
    });

    test('rejects role assignments on an institution type', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);

        asUser($this->admin)->put(route('types.roles.sync', $type), [
            'roles' => [Role::query()->first()->id],
        ])->assertStatus(403);
    });
});
