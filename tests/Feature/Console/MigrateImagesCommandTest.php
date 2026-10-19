<?php

use App\Jobs\Media\BackfillLegacyImagesJob;
use App\Models\News;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Media\ImageMigration;
use App\Services\Media\LegacyImageBackfill;
use App\Services\Media\LegacyImageImporter;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

pest()->use(RefreshDatabase::class);

test('status after column removal checks the remaining targets', function (): void {
    $this->artisan('media:migrate-images', ['--status' => true])
        ->expectsOutputToContain('users.profile_photo')
        ->doesntExpectOutputToContain('news.image')
        ->expectsOutputToContain('0 media to upgrade, 0 legacy rows to import')
        ->assertSuccessful();

    expect(array_keys(app(LegacyImageBackfill::class)->status()))->toBe([
        'institutions.logo', 'users.profile_photo', 'dutiables.photo',
    ]);
});

test('an explicit retired target is a successful no-op', function (array $flags): void {
    Bus::fake();

    $this->artisan('media:migrate-images', ['--target' => 'news.image', ...$flags])
        ->expectsOutputToContain('news.image has been retired')
        ->assertSuccessful();

    Bus::assertNothingDispatched();
    Bus::assertNothingBatched();
})->with(['queued' => [[]], 'status' => [['--status' => true]], 'foreground' => [['--sync' => true]]]);

test('rerunning the migration queues only remaining legacy targets', function (): void {
    Bus::fake();
    $user = User::factory()->create(['profile_photo_path' => '/uploads/contacts/missing.jpg']);

    $this->artisan('media:migrate-images', ['--detach' => true])->assertSuccessful();

    Bus::assertBatched(fn (PendingBatch $batch): bool => $batch->jobs->every(
        fn ($job): bool => $job instanceof BackfillLegacyImagesJob && $job->target === 'users.profile_photo',
    ) && $batch->jobs->contains(fn (BackfillLegacyImagesJob $job): bool => in_array($user->id, $job->ids, true)));
    expect(array_keys(app(ImageMigration::class)->plan(null, null)['imports']))->toBe([
        'institutions.logo', 'users.profile_photo', 'dutiables.photo',
    ]);
});

test('a delayed backfill job for a retired target leaves the owner alone', function (): void {
    $news = News::factory()->for(Tenant::query()->first())->create();

    (new BackfillLegacyImagesJob('news.image', [$news->id]))->handle(app(LegacyImageImporter::class));

    expect($news->fresh()->getMedia('image'))->toBeEmpty();
    $this->assertModelExists($news);
});
