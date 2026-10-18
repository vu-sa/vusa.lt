<?php

use App\Actions\Media\AddImageMedia;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

it('merges through the dialog with a selected duty email and automatic photo preservation', function (): void {
    Storage::fake('spatieMediaLibrary');
    $tenant = Tenant::query()->first();
    $actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $kept = makeUser($tenant);
    $source = User::factory()->create(['name' => $kept->name]);
    $duty = Duty::factory()->create(['institution_id' => $actor->duties()->first()->institution_id]);
    $period = Dutiable::factory()->forUser($source)->forDuty($duty)->create([
        'start_date' => '2024-01-01', 'end_date' => null,
        'additional_email' => null, 'additional_photo' => null, 'additional_photo_focal_point' => null,
    ]);
    app(AddImageMedia::class)->execute($source, UploadedFile::fake()->image('source.jpg'), 'profile_photo', properties: ['focal_point' => '20% 30%']);

    $page = loginAsAdmin($actor);
    $page->navigate("/mano/users/{$kept->id}/edit");
    waitForInertiaRender($page, '[data-testid=existing-duplicates]');
    $page->click('[data-testid=existing-duplicates] button');
    $page->click('[data-merge-target="'.$kept->id.'"]');
    $page->assertVisible('[data-slot=merge-duty-emails]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 1000);
        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            $page->assertNoJavaScriptErrors()->screenshot(fullPage: false, filename: "member-merge-{$width}-{$theme}");
        }
    }

    $page->click('[data-slot=merge-duty-emails] [role=checkbox]');
    $page->click('[data-slot=merge-submit]');
    $page->assertNotPresent('[data-slot=merge-duty-emails]')->assertNoJavaScriptErrors();

    expect($period->fresh()->dutiable_id)->toBe($kept->id)
        ->and($period->fresh()->additional_email)->toBe($source->email)
        ->and($period->fresh()->getFirstMedia('photo')->getCustomProperty('focal_point'))->toBe('20% 30%');
    $this->assertModelMissing($source);
});
