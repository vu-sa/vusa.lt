<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    test('can store an institution type', function (): void {
        asUser($this->admin)->post(route('types.store'), [
            'title' => ['lt' => 'Padalinys', 'en' => 'Unit'],
            'model_type' => MorphMap::alias(Institution::class),
        ])->assertRedirect(route('types.index'));

        expect(Type::query()->where('model_type', MorphMap::alias(Institution::class))->exists())->toBeTrue();
    });

    test('can sync institutions onto an institution type', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Institution::class)]);
        $institution = Institution::factory()->for($this->tenant)->create();

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Padalinys', 'en' => 'Unit'],
            'model_type' => MorphMap::alias(Institution::class),
            'institutions' => [$institution->id],
        ])->assertRedirect();

        expect($type->fresh()->institutions->pluck('id'))->toContain($institution->id);
    });

    test('can sync duties and roles onto a duty type', function (): void {
        $type = Type::factory()->create(['model_type' => MorphMap::alias(Duty::class)]);
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $role = Role::query()->first();

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Pareigos', 'en' => 'Duty'],
            'model_type' => MorphMap::alias(Duty::class),
            'duties' => [$duty->id],
            'roles' => [$role->id],
        ])->assertRedirect();

        expect($type->fresh()->duties->pluck('id'))->toContain($duty->id)
            ->and($type->fresh()->roles->pluck('id'))->toContain($role->id);
    });
});
