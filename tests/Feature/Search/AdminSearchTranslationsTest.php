<?php

use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Resource;
use App\Models\ResourceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('institution search preserves bilingual names and type labels alongside stable facet values', function (string $locale): void {
    app()->setLocale($locale);
    $institution = Institution::factory()->create(['name' => ['lt' => 'Taryba', 'en' => 'Council']]);
    $type = InstitutionType::factory()->create(['title' => ['lt' => 'Komitetas', 'en' => 'Committee']]);
    $institution->types()->sync([$type->id]);

    $document = $institution->fresh()->toSearchableArray();

    expect($document['name_lt'])->toBe('Taryba')
        ->and($document['name_en'])->toBe('Council')
        ->and($document['type_titles'])->toBe(['Komitetas'])
        ->and($document['type_titles_lt'])->toBe(['Komitetas'])
        ->and($document['type_titles_en'])->toBe(['Committee'])
        ->and($document['type_ids'])->toBe([$type->id])
        ->and($document['tenant_ids'])->toBe([$institution->tenant_id])
        ->and($document['tenant_shortname'])->toBe($institution->tenant->shortname);
})->with(['lt', 'en']);

test('duty search preserves bilingual type labels alongside stable facet values', function (): void {
    $duty = Duty::factory()->create(['name' => ['lt' => 'Atstovas', 'en' => 'Representative']]);
    $type = DutyType::factory()->create(['title' => ['lt' => 'Atstovai', 'en' => 'Representatives']]);
    $duty->types()->sync([$type->id]);

    $document = $duty->fresh()->toSearchableArray();

    expect($document['type_titles'])->toBe(['Atstovai'])
        ->and($document['type_titles_lt'])->toBe(['Atstovai'])
        ->and($document['type_titles_en'])->toBe(['Representatives']);
});

test('resource search preserves bilingual category names alongside its stable facet value', function (): void {
    $category = ResourceCategory::factory()->create(['name' => ['lt' => 'Technika', 'en' => 'Equipment']]);
    $resource = Resource::factory()->create(['resource_category_id' => $category->id]);

    $document = $resource->toSearchableArray();

    expect($document['category_name'])->toBe('Technika')
        ->and($document['category_name_lt'])->toBe('Technika')
        ->and($document['category_name_en'])->toBe('Equipment');
});
