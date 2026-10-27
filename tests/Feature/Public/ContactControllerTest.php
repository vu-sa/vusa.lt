<?php

use App\Actions\Media\AddImageMedia;
use App\Enums\TenantType;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Tenant;
use App\Services\SystemDutyTypes;
use App\Settings\DutyTypeSettings;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

test('institution contact URLs follow deactivation and reactivation', function (string $routeName, string $locale): void {
    $institution = Institution::factory()->create([
        'tenant_id' => Tenant::main()->id,
        'alias' => 'visibility-test',
        'is_active' => true,
        'name' => ['lt' => 'Matoma institucija', 'en' => 'Visible institution'],
    ]);
    $url = LocalizedRouteSlugs::route($routeName, [
        'subdomain' => 'www', 'lang' => $locale,
        'institution' => $routeName === 'contacts.alias' ? $institution->alias : $institution->id,
    ], $locale);

    $this->get($url)->assertOk();

    $institution->update(['is_active' => false]);
    $this->get($url)->assertNotFound();

    $institution->update(['is_active' => true]);
    $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Public/Contacts/ShowInstitution')
        ->where('institution.id', $institution->id)
    );
})->with(['id' => 'contacts.institution', 'alias' => 'contacts.alias'])->with(['lt', 'en']);

test('logging in does not expose inactive public institution pages', function (): void {
    $tenant = Tenant::main();
    $institution = Institution::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

    asUser(makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant))
        ->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertNotFound();
});

test('duty type contact URLs follow the primary institution visibility', function (string $subdomain, string $locale): void {
    $tenant = $subdomain === 'www' ? Tenant::main() : Tenant::where('alias', $subdomain)->firstOrFail();
    $alias = $tenant->isMain() ? 'centrinis-biuras' : $tenant->alias;
    $institution = Institution::where('alias', $alias)->first()
        ?? Institution::factory()->create([
            'tenant_id' => $tenant->id,
            'alias' => $alias,
            'name' => ['lt' => 'Padalinio institucija', 'en' => 'Unit institution'],
        ]);
    $institution->update(['is_active' => true]);
    $type = DutyType::firstOrCreate(['id' => 6], ['title' => ['lt' => 'Koordinatoriai', 'en' => 'Coordinators']]);
    $settings = app(DutyTypeSettings::class);
    $settings->coordinator_type_id = $type->id;
    $settings->save();
    app(SystemDutyTypes::class)->flush();
    $url = LocalizedRouteSlugs::route('contacts.dutyType', ['subdomain' => $subdomain, 'lang' => $locale, 'type' => 'koordinatoriai'], $locale);

    $this->get($url)->assertOk();
    $institution->update(['is_active' => false]);
    $this->get($url)->assertNotFound();
    $institution->update(['is_active' => true]);
    $this->get($url)->assertOk();
})->with(['www', 'mif'])->with(['lt', 'en']);

test('padaliniai category lists only active institutions and reflects reactivation', function (): void {
    $tenant = Tenant::where('type', TenantType::Padalinys)->firstOrFail();
    $type = InstitutionType::where('slug', 'padaliniai')->firstOrFail();
    $active = Institution::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
    $inactive = Institution::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);
    $active->types()->attach($type);
    $inactive->types()->attach($type);
    $url = route('contacts.category', ['subdomain' => 'www', 'lang' => 'lt', 'type' => $type->slug]);

    $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Public/Contacts/ShowContactCategory')
        ->where('institutions', fn ($institutions) => collect($institutions)->pluck('id')->contains($active->id)
            && ! collect($institutions)->pluck('id')->contains($inactive->id))
    );

    $inactive->update(['is_active' => true]);
    $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('institutions', fn ($institutions) => collect($institutions)->pluck('id')->contains($inactive->id))
    );
});

test('padaliniai category provides institution photos and preserves the logo fallback', function (string $locale): void {
    Storage::fake('spatieMediaLibrary');
    $tenant = Tenant::where('type', TenantType::Padalinys)->firstOrFail();
    $type = InstitutionType::where('slug', 'padaliniai')->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create(['is_active' => true]);
    $institution->types()->attach($type);
    app(AddImageMedia::class)->execute($institution, UploadedFile::fake()->image('team.jpg'), 'image');
    $institution->getFirstMedia('image')->setCustomProperty('focal_point', '40% 60%')->save();
    $photo = $institution->fresh()->imageData('image');

    $withoutPhoto = Institution::factory()->for($tenant)->create([
        'is_active' => true,
        'logo_url' => '/images/unit-logo.svg',
    ]);
    $withoutPhoto->types()->attach($type);

    $url = LocalizedRouteSlugs::route('contacts.category', [
        'subdomain' => 'www', 'lang' => $locale, 'type' => $type->slug,
    ], $locale);

    $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Public/Contacts/ShowContactCategory')
        ->where('institutions', function ($institutions) use ($institution, $withoutPhoto, $photo): bool {
            $withPhoto = collect($institutions)->firstWhere('id', $institution->id);
            $fallback = collect($institutions)->firstWhere('id', $withoutPhoto->id);

            expect($withPhoto)->toHaveKey('image_url', $photo['url'])
                ->toHaveKey('image_media', $photo)
                ->not->toHaveKey('media');
            expect($fallback)->toHaveKey('image_url', null)
                ->toHaveKey('image_media', null)
                ->toHaveKey('logo_url', '/images/unit-logo.svg');

            return true;
        })
    );
})->with(['lt', 'en']);

test('an institution provides its image in public props', function (): void {
    Storage::fake('spatieMediaLibrary');
    $tenant = Tenant::main();
    $institution = Institution::factory()->create([
        'tenant_id' => $tenant->id,
        'is_active' => true,
    ]);
    app(AddImageMedia::class)->execute($institution, UploadedFile::fake()->image('sabas.jpg'), 'image');
    $institution->getFirstMedia('image')->setCustomProperty('focal_point', '40% 60%')->save();
    $tenant->update(['primary_institution_id' => $institution->id]);

    $fullUrl = $institution->fresh()->imageData('image')['url'];
    $thumbUrl = $institution->fresh()->imageData('image')['thumb'];

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('institution.image_url', $fullUrl)
            ->missing('institution.media')
            ->where('tenants', fn ($tenants) => collect($tenants)->firstWhere('id', $tenant->id)['primary_institution']['image_url'] === $thumbUrl)
        );
});

test('an institution’s old alias redirects to its current address in the visitor’s language', function (string $locale): void {
    $tenant = Tenant::query()->where('alias', 'mif')->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create(['alias' => 'senas-adresas', 'is_active' => true]);
    $institution->update(['alias' => 'naujas-adresas']);

    $oldUrl = LocalizedRouteSlugs::route('contacts.alias', ['subdomain' => 'www', 'institution' => 'senas-adresas'], $locale);

    $this->get($oldUrl)->assertMovedPermanently()->assertRedirect($institution->publicUrl($locale));
    $this->get($institution->publicUrl($locale))->assertOk();
})->with(['lt', 'en']);

test('an alias no institution ever held alone is not found', function (): void {
    $url = LocalizedRouteSlugs::route('contacts.alias', ['subdomain' => 'www', 'institution' => 'padalinio-studentu-atstovai-8'], 'lt');

    $this->get($url)->assertNotFound();
});

test('an institution id in the alias position still opens the institution', function (): void {
    $institution = Institution::factory()->create(['tenant_id' => Tenant::main()->id, 'is_active' => true]);
    $url = LocalizedRouteSlugs::route('contacts.alias', ['subdomain' => 'www', 'institution' => $institution->id], 'lt');

    $this->get($url)->assertRedirect(LocalizedRouteSlugs::route('contacts.institution', ['subdomain' => 'www', 'institution' => $institution->id], 'lt'));
});
