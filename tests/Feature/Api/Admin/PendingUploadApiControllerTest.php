<?php

use App\Models\PendingUpload;
use App\Models\Tenant;
use App\Support\Media\ImageConversions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = makeUser(Tenant::query()->first());
});

test('guests cannot stage an image', function (): void {
    $this->postJson(route('api.v1.admin.pendingUploads.store'), [
        'image' => UploadedFile::fake()->image('photo.jpg'),
    ])->assertUnauthorized();
});

test('a staged image is stored as a bounded webp with its dimensions', function (): void {
    Storage::fake('spatieMediaLibrary');

    $response = asUser($this->user)->postJson(route('api.v1.admin.pendingUploads.store'), [
        'image' => UploadedFile::fake()->image('Rudens šventė.jpg', 3600, 1800),
    ])->assertCreated();

    $upload = PendingUpload::query()->whereBelongsTo($this->user)->sole();
    $media = $upload->getFirstMedia(PendingUpload::COLLECTION);

    expect($media->mime_type)->toBe('image/webp')
        ->and($media->file_name)->toBe('rudens-svente.webp')
        ->and($media->getCustomProperty('width'))->toBe(ImageConversions::ORIGINAL_MAX)
        ->and($media->getCustomProperty('height'))->toBe(1200);

    $response->assertJsonPath('data.id', $media->id)
        ->assertJsonPath('data.width', ImageConversions::ORIGINAL_MAX);
});

test('only images are accepted', function (): void {
    asUser($this->user)->postJson(route('api.v1.admin.pendingUploads.store'), [
        'image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    ])->assertJsonValidationErrors('image');
});

test('a user cannot hoard unsaved uploads', function (): void {
    Storage::fake('spatieMediaLibrary');
    PendingUpload::query()->insert(collect(range(1, PendingUpload::MAX_PER_USER))
        ->map(fn () => ['id' => (string) Str::ulid(), 'user_id' => $this->user->id, 'created_at' => now(), 'updated_at' => now()])
        ->all());

    asUser($this->user)->postJson(route('api.v1.admin.pendingUploads.store'), [
        'image' => UploadedFile::fake()->image('photo.jpg'),
    ])->assertJsonValidationErrors(['image' => __('files.pending_upload_limit')]);
});

test('on staging uploads go to staging\'s own media disk while shared folders stay read-only', function (): void {
    Storage::fake('stagingMedia');
    Storage::fake('spatieMediaLibrary');
    config([
        'app.env' => 'staging',
        'app.files_read_only' => true,
        'app.staging_basic_auth_enabled' => false,
        'media-library.disk_name' => 'stagingMedia',
    ]);

    asUser($this->user)->postJson(route('api.v1.admin.pendingUploads.store'), [
        'image' => UploadedFile::fake()->image('photo.jpg'),
    ])->assertCreated();

    expect(PendingUpload::query()->sole()->getFirstMedia(PendingUpload::COLLECTION)->disk)->toBe('stagingMedia');

    asUser($this->user)->postJson(route('files.uploadImage'), [
        'image' => UploadedFile::fake()->image('photo.jpg'),
        'path' => 'news',
    ])->assertForbidden();
});
