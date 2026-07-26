<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();

    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser($this->tenant);
});

describe('unauthorized access', function () {
    test('a simple user cannot index types', function () {
        asUser($this->user)->get(route('types.index'))->assertStatus(403);
    });

    test('a simple user cannot store a type', function () {
        asUser($this->user)->post(route('types.store'), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => Duty::class,
        ])->assertStatus(403);
    });
});

describe('model_type allowlist', function () {
    /**
     * `model_type` used to be turned into a method name and invoked on the model
     * (`$type->$modelType()->sync(...)`). Anything outside the allowlist must now
     * be a validation error — never a dynamic dispatch, and never a 500.
     */
    test('rejects a model_type outside the allowlist when storing', function (string $modelType) {
        asUser($this->admin)->post(route('types.store'), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => $modelType,
        ])->assertSessionHasErrors('model_type');

        expect(Type::query()->where('model_type', $modelType)->exists())->toBeFalse();
    })->with([
        'roles relation' => ['App\Models\Role'],
        'a relation that would 500' => ['App\Models\Descendant'],
        'an arbitrary class' => ['App\Models\User'],
        'not a class at all' => ['nonsense'],
        'empty string' => [''],
    ]);

    test('rejects a model_type outside the allowlist when updating', function () {
        $type = Type::factory()->create(['model_type' => Duty::class]);

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => Role::class,
        ])->assertSessionHasErrors('model_type');

        expect($type->fresh()->model_type)->toBe(Duty::class);
    });

    test('a bogus model_type cannot sync roles onto a type', function () {
        $type = Type::factory()->create(['model_type' => Institution::class]);
        $role = Role::query()->first();

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Tipas', 'en' => 'Type'],
            'model_type' => Role::class,
            'roles' => [$role->id],
        ])->assertSessionHasErrors('model_type');

        expect($type->fresh()->roles)->toHaveCount(0);
    });
});

describe('allowed model types still work', function () {
    test('can store an institution type', function () {
        asUser($this->admin)->post(route('types.store'), [
            'title' => ['lt' => 'Padalinys', 'en' => 'Unit'],
            'model_type' => Institution::class,
        ])->assertRedirect(route('types.index'));

        expect(Type::query()->where('model_type', Institution::class)->exists())->toBeTrue();
    });

    test('can sync institutions onto an institution type', function () {
        $type = Type::factory()->create(['model_type' => Institution::class]);
        $institution = Institution::factory()->for($this->tenant)->create();

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Padalinys', 'en' => 'Unit'],
            'model_type' => Institution::class,
            'institutions' => [$institution->id],
        ])->assertRedirect();

        expect($type->fresh()->institutions->pluck('id'))->toContain($institution->id);
    });

    test('can sync duties and roles onto a duty type', function () {
        $type = Type::factory()->create(['model_type' => Duty::class]);
        $duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
        $role = Role::query()->first();

        asUser($this->admin)->patch(route('types.update', $type), [
            'title' => ['lt' => 'Pareigos', 'en' => 'Duty'],
            'model_type' => Duty::class,
            'duties' => [$duty->id],
            'roles' => [$role->id],
        ])->assertRedirect();

        expect($type->fresh()->duties->pluck('id'))->toContain($duty->id);
        expect($type->fresh()->roles->pluck('id'))->toContain($role->id);
    });
});
