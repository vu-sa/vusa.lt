<?php

use App\Models\Problem;
use App\Models\ProblemCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    // Categories are shared by every padalinys, so only a global problem editor manages them.
    $this->central = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', $this->tenant);
    $this->coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
});

test('a padalinys coordinator cannot open or change the categories', function (): void {
    $category = ProblemCategory::factory()->create();

    asUser($this->coordinator)->get(route('problemCategories.index'))->assertForbidden();
    asUser($this->coordinator)->post(route('problemCategories.store'), ['name' => ['lt' => 'Nauja']])->assertForbidden();
    asUser($this->coordinator)->patch(route('problemCategories.update', $category), ['name' => ['lt' => 'Pervadinta']])->assertForbidden();
    asUser($this->coordinator)->delete(route('problemCategories.destroy', $category))->assertForbidden();
});

test('a central coordinator lists categories with how many problems use them', function (): void {
    $category = ProblemCategory::factory()->create();
    Problem::factory()->create(['tenant_id' => $this->tenant->id])->categories()->attach($category);

    asUser($this->central)->get(route('problemCategories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Problems/IndexProblemCategory')
            ->where('problemCategories.0.problems_count', 1)
            ->where('abilities.delete', true));
});

test('creating derives a unique slug, renaming keeps it', function (): void {
    ProblemCategory::factory()->create(['slug' => 'studiju-procesas']);

    asUser($this->central)->post(route('problemCategories.store'), [
        'name' => ['lt' => 'Studijų procesas', 'en' => 'Study process'],
    ])->assertSessionHasNoErrors();

    $category = ProblemCategory::query()->where('slug', 'studiju-procesas-2')->sole();

    asUser($this->central)->patch(route('problemCategories.update', $category), [
        'name' => ['lt' => 'Studijos'],
    ])->assertSessionHasNoErrors();

    expect($category->fresh())
        ->slug->toBe('studiju-procesas-2')
        ->getTranslation('name', 'lt')->toBe('Studijos');
});

test('a category in use cannot be deleted, an unused one can', function (): void {
    $used = ProblemCategory::factory()->create();
    $unused = ProblemCategory::factory()->create();
    // A trashed problem still counts: restoring it would bring the category back.
    tap(Problem::factory()->create(['tenant_id' => $this->tenant->id]), function (Problem $problem) use ($used): void {
        $problem->categories()->attach($used);
        $problem->delete();
    });

    asUser($this->central)->delete(route('problemCategories.destroy', $used))->assertSessionHas('error');
    asUser($this->central)->delete(route('problemCategories.destroy', $unused))->assertSessionHas('success');

    expect(ProblemCategory::query()->find($used->id))->not->toBeNull()
        ->and(ProblemCategory::query()->find($unused->id))->toBeNull();
});
