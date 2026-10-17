<?php

use App\Actions\Media\AddImageMedia;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

test('the portrait endpoint requires authentication and the target user permission', function (): void {
    $tenant = Tenant::query()->first();
    $coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $tenant);
    $member = makeUser($tenant);
    $url = route('api.v1.admin.users.photo', $member);

    $this->getJson($url)->assertUnauthorized();
    asUser(makeUser($tenant))->getJson($url)->assertForbidden();
    Storage::fake('spatieMediaLibrary');
    $media = app(AddImageMedia::class)->execute($member, UploadedFile::fake()->image('portrait.jpg', 1000, 1500), 'profile_photo');
    asUser($coordinator)->getJson($url)->assertOk()->assertJsonPath('data.image.id', $media->id)
        ->assertJsonPath('data.image.width', 1000);
});
