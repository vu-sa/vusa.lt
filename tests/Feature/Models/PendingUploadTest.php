<?php

use App\Models\PendingUpload;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

test('pruning removes unclaimed uploads with their files and keeps recent ones', function (): void {
    Storage::fake('spatieMediaLibrary');
    $user = makeUser(Tenant::query()->first());
    $abandoned = stageImage($user);
    $abandonedPath = $abandoned->getPathRelativeToRoot();
    $this->travel(PendingUpload::TTL_DAYS + 1)->days();
    $recent = stageImage($user);

    $this->artisan('model:prune', ['--model' => [PendingUpload::class]])->assertSuccessful();

    expect(PendingUpload::query()->pluck('id')->all())->toBe([$recent->model_id]);
    Storage::disk('spatieMediaLibrary')->assertMissing($abandonedPath);
});
