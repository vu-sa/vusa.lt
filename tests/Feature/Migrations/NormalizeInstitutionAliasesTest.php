<?php

use App\Models\Institution;
use App\Models\PublicUrl;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

function normalizeInstitutionAliasesMigration(): object
{
    return require base_path('database/migrations/2026_10_19_145811_normalize_institution_aliases.php');
}

function institutionAliasUniqueIndexMigration(): object
{
    return require base_path('database/migrations/2026_10_19_145812_add_unique_index_to_institutions_alias.php');
}

function seededTenant(string $alias): Tenant
{
    return Tenant::query()->where('alias', $alias)->firstOrFail();
}

/** Seeded values predate the unique index, so they are written past it. */
function institutionWithAlias(Tenant $tenant, string $name, string $alias): Institution
{
    $institution = Institution::factory()->for($tenant)->create(['name' => ['lt' => $name, 'en' => $name]]);
    DB::table('institutions')->where('id', $institution->id)->update(['alias' => $alias]);

    return $institution->refresh();
}

beforeEach(function (): void {
    institutionAliasUniqueIndexMigration()->down();
});

test('a shared alias becomes one alias per institution, with no redirect', function (): void {
    $fsf = seededTenant('fsf');
    $ff = seededTenant('ff');
    $psychology = institutionWithAlias($fsf, '(BA) Psichologija', 'padalinio-studentu-atstovai-8');
    $sociology = institutionWithAlias($fsf, '(MA) Sociologija', 'padalinio-studentu-atstovai-8');
    $ffSociology = institutionWithAlias($ff, '(MA) Sociologija', 'padalinio-studentu-atstovai-8');

    normalizeInstitutionAliasesMigration()->up();

    expect($psychology->refresh()->alias)->toBe('ba-psichologija')
        ->and($sociology->refresh()->alias)->toBe('fsf-ma-sociologija')
        ->and($ffSociology->refresh()->alias)->toBe('ff-ma-sociologija')
        ->and(PublicUrl::query()->where('url', 'like', '%padalinio-studentu-atstovai-8')->exists())->toBeFalse();
});

test('a readable or abbreviated old alias keeps redirecting in both languages', function (string $oldAlias): void {
    $institution = institutionWithAlias(seededTenant('kf'), 'VU KF Ginčų nagrinėjimo komisija', $oldAlias);

    normalizeInstitutionAliasesMigration()->up();

    expect($institution->refresh()->alias)->toBe('vu-kf-gincu-nagrinejimo-komisija')
        ->and($institution->publicUrls()->pluck('locale')->sort()->values()->all())->toBe(['en', 'lt']);
})->with(['abbreviation' => 'kfgnk', 'reworded' => 'gincu-nagrinejimo-komisija-kf']);

test('an old alias nobody could have meant to link to is renamed without a redirect', function (string $oldAlias, bool $trashed): void {
    $institution = institutionWithAlias(seededTenant('mif'), 'Programų sistemos (BA)', $oldAlias);

    if ($trashed) {
        $institution->delete();
    }

    normalizeInstitutionAliasesMigration()->up();

    expect(Institution::withTrashed()->find($institution->id)->alias)->toBe('programu-sistemos-ba')
        ->and($institution->publicUrls()->exists())->toBeFalse();
})->with([
    'not URL-safe' => ['Programų sistemos (BA)', false],
    'seeded placeholder' => ['padalinio-studentu-atstovai-17', false],
    'trashed' => ['programu-sistemos', true],
]);

test('a trashed institution never takes an alias a live one gives up, so its redirect survives', function (): void {
    $club = institutionWithAlias(seededTenant('vusa'), 'VU Kendo klubas', 'kendo-klubas');
    $trashed = institutionWithAlias(seededTenant('vusa'), 'Kendo klubas', 'pkp-119');
    $trashed->delete();

    normalizeInstitutionAliasesMigration()->up();

    expect($club->refresh()->alias)->toBe('vu-kendo-klubas')
        ->and(Institution::withTrashed()->find($trashed->id)->alias)->not->toBe('kendo-klubas')
        ->and($club->publicUrls()->count())->toBe(2);
});

test('the main tenant keeps the bare slug another tenant gives up, which then cannot redirect', function (): void {
    $siauliai = institutionWithAlias(seededTenant('sa'), 'VU ŠA Taryba', 'vu-sa-taryba');
    $vusa = institutionWithAlias(seededTenant('vusa'), 'VU SA Taryba', 'taryba');

    normalizeInstitutionAliasesMigration()->up();

    expect($vusa->refresh()->alias)->toBe('vu-sa-taryba')
        ->and($siauliai->refresh()->alias)->toBe('sa-vu-sa-taryba')
        ->and($vusa->publicUrls()->count())->toBe(2)
        ->and($siauliai->publicUrls()->exists())->toBeFalse();
});

test('the tenant institution and the central office keep their aliases', function (): void {
    $tenant = seededTenant('chgf');
    $own = institutionWithAlias($tenant, 'VU SA Chemijos ir geomokslų fakultete', 'chgf');
    $centralOffice = Institution::query()->where('alias', 'centrinis-biuras')->first()
        ?? institutionWithAlias(Tenant::main() ?? $tenant, 'Centrinis biuras', 'centrinis-biuras');

    normalizeInstitutionAliasesMigration()->up();

    expect($own->refresh()->alias)->toBe('chgf')
        ->and($centralOffice->refresh()->alias)->toBe('centrinis-biuras');
});

test('trashed institutions are renamed too, so the unique index can go on', function (): void {
    $tenant = seededTenant('tf');
    $trashed = institutionWithAlias($tenant, 'VU TF ginčų nagrinėjimo komisijos narė', 'padalinio-studentu-atstovai-16');
    institutionWithAlias($tenant, 'VU TF ginčų nagrinėjimo komisija', 'padalinio-studentu-atstovai-16');
    $trashed->delete();

    normalizeInstitutionAliasesMigration()->up();

    expect(Institution::withTrashed()->find($trashed->id)->alias)->toBe('vu-tf-gincu-nagrinejimo-komisijos-nare');
    institutionAliasUniqueIndexMigration()->up();
});

test('the unique index refuses to go on while aliases are still shared', function (): void {
    $tenant = Tenant::factory()->create();
    institutionWithAlias($tenant, 'A', 'shared');
    institutionWithAlias($tenant, 'B', 'shared');

    expect(fn () => institutionAliasUniqueIndexMigration()->up())->toThrow(RuntimeException::class);
});
