<?php

use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

pest()->use(RefreshDatabase::class);

it('confirms an uploaded image save and permits saving again', function (string $kind): void {
    Storage::fake('spatieMediaLibrary');
    Storage::fake('local');
    Queue::fake([PerformConversionsJob::class]);
    $tenant = Tenant::first();
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $record = $kind === 'news'
        ? News::factory()->for($tenant)->create(['lang' => 'lt', 'show_breadcrumbs' => true])
        : Page::factory()->for($tenant)->create(['lang' => 'lt', 'show_breadcrumbs' => true, 'show_title' => true, 'show_table_of_contents' => true]);
    $record->update(['updated_at' => now()->subMinute()]);
    // A small WebP skips the compressor's external worker, keeping this save test offline.
    $upload = UploadedFile::fake()->image('editor-cover.webp', 800, 400);
    $uploadPath = $upload->storeAs('browser-fixtures', 'editor-cover.webp', 'local');
    $page = loginAsAdmin($user, "/mano/{$kind}/{$record->id}/edit");
    waitForInertiaRender($page, '[data-slot=upload] input[type=file]');

    $page->attach('[data-slot=upload] input[type=file]', Storage::disk('local')->path($uploadPath));
    waitForInertiaRender($page, '[role=status]:has-text("Neišsaugota")');
    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[data-sonner-toast]:has-text("Išsaugota")');
    $page->assertDontSee('Kažkas išsaugojo naujesnę versiją');
    expect($record->fresh()->getFirstMedia($kind === 'news' ? 'image' : 'featured_image'))->not->toBeNull();

    $page->click('[data-testid=form-page-save]');
    waitForInertiaRender($page, '[data-sonner-toast]:nth-child(2):has-text("Išsaugota")');
    waitForInertiaRender($page, '[role=status]:has-text("Visi pakeitimai išsaugoti")');
    $page->assertDontSee('Kažkas išsaugojo naujesnę versiją')->assertNoJavaScriptErrors();
    $page->screenshot(fullPage: false, filename: 'content-image-save-'.$kind);
})->with(['news', 'pages']);

it('renders pending upload feedback in the built editor on phone and desktop in both themes', function (): void {
    Storage::fake('spatieMediaLibrary');
    Storage::fake('local');
    Queue::fake([PerformConversionsJob::class]);
    $tenant = Tenant::first();
    $user = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    $record = News::factory()->for($tenant)->create(['lang' => 'lt']);
    $uploadPath = UploadedFile::fake()->image('upload-feedback.webp', 800, 400)
        ->storeAs('browser-fixtures', 'upload-feedback.webp', 'local');
    $page = loginAsAdmin($user, "/mano/news/{$record->id}/edit");
    waitForInertiaRender($page, '[data-slot=upload] input[type=file]');
    $page->script('(() => {
        const fetch = window.fetch.bind(window);
        window.fetch = async (...args) => {
            if (String(args[0]).includes("/pending-uploads")) {
                await new Promise(resolve => { window.releaseImageUpload = resolve; });
            }
            return fetch(...args);
        };
    })()');
    $page->attach('[data-slot=upload] input[type=file]', Storage::disk('local')->path($uploadPath));
    waitForInertiaRender($page, '[data-slot=image-upload-status]');

    foreach ([390, 1440] as $width) {
        $page->resize($width, 1000);
        foreach (['light', 'dark'] as $theme) {
            $page->script('document.documentElement.classList.toggle("dark", '.($theme === 'dark' ? 'true' : 'false').')');
            $page->script('document.querySelector("[data-slot=image-upload-status]").scrollIntoView({ block: "center" })');
            $page->assertNoJavaScriptErrors()->screenshot(fullPage: false, filename: "image-upload-{$width}-{$theme}");
        }
    }

    $page->script('window.releaseImageUpload()');
    waitForInertiaRender($page, '[data-slot=image-upload-success]');
    $page->assertNoJavaScriptErrors();
});
