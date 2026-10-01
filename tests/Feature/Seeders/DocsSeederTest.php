<?php

use App\Models\Institution;
use App\Models\User;
use Database\Seeders\DocsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('documentation fixtures assign duty and institution types from their separate collections', function (): void {
    $this->seed(DocsSeeder::class);

    $representative = User::query()->where('email', DocsSeeder::REPRESENTATIVE_EMAIL)->firstOrFail();
    $council = Institution::query()->where('name->lt', DocsSeeder::COUNCIL_INSTITUTION)->firstOrFail();

    expect($representative->duties()->whereHas('types', fn ($query) => $query->where('slug', 'studentu-atstovai'))->count())->toBe(3)
        ->and($council->types()->pluck('slug')->all())->toContain('studentu-atstovu-organas');
});
