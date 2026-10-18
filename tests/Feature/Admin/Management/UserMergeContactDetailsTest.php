<?php

use App\Actions\Media\AddImageMedia;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $institution = $this->actor->duties()->first()->institution;
    $this->kept = User::factory()->create(['name' => 'Jonas Jonaitis']);
    $this->source = User::factory()->create(['name' => 'Jonas Jonaitis']);
    $this->keptDuty = Duty::factory()->create(['institution_id' => $institution->id]);
    $this->sourceDuty = Duty::factory()->create(['institution_id' => $institution->id]);
    $this->keptPeriod = Dutiable::factory()->state(['additional_email' => null, 'additional_photo' => null, 'additional_photo_focal_point' => null])->forUser($this->kept)->forDuty($this->keptDuty)->create(['start_date' => '2024-01-01', 'end_date' => null]);
    $this->sourcePeriod = Dutiable::factory()->state(['additional_email' => null, 'additional_photo' => null, 'additional_photo_focal_point' => null])->forUser($this->source)->forDuty($this->sourceDuty)->create(['start_date' => '2024-06-01', 'end_date' => null]);
    $this->payload = fn (array $extra = []) => [
        'kept_user_id' => $this->kept->id, 'source_user_ids' => [$this->source->id],
        'login_email' => $this->kept->email, ...$extra,
    ];
});

test('preview lists permitted empty periods without changing them', function (): void {
    asUser($this->actor)->postJson(route('api.v1.admin.users.mergePreview'), ($this->payload)())
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->sourcePeriod->id)
        ->assertJsonPath('data.0.email', $this->source->email);

    expect($this->sourcePeriod->fresh()->additional_email)->toBeNull();
    $this->assertModelExists($this->source);
});

test('merging without selecting an email leaves the additional email empty', function (): void {
    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)())->assertSessionHasNoErrors();

    expect($this->sourcePeriod->fresh()->additional_email)->toBeNull();
});

test('selected periods receive their source account email while unchecked periods stay empty', function (): void {
    $past = Dutiable::factory()->state(['additional_email' => null, 'additional_photo' => null, 'additional_photo_focal_point' => null])->forUser($this->source)->forDuty($this->sourceDuty)->create(['start_date' => '2020-01-01', 'end_date' => '2021-01-01']);

    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)(['dutiable_email_ids' => [$this->sourcePeriod->id]]))
        ->assertSessionHasNoErrors();

    expect($this->sourcePeriod->fresh()->additional_email)->toBe($this->source->email)
        ->and($past->fresh()->additional_email)->toBeNull()
        ->and($this->kept->fresh()->email)->toBe($this->kept->email);
});

test('existing additional emails are preserved in separate and overlapping periods', function (bool $overlap): void {
    $period = $overlap ? $this->keptPeriod : $this->sourcePeriod;
    $period->update(['additional_email' => 'existing@vusa.lt']);
    if ($overlap) {
        $this->sourcePeriod->update(['duty_id' => $this->keptDuty->id]);
    }

    asUser($this->actor)->postJson(route('api.v1.admin.users.mergePreview'), ($this->payload)())
        ->assertOk()->assertJsonCount(0, 'data');
    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)(['dutiable_email_ids' => [$this->sourcePeriod->id]]))
        ->assertSessionHasErrors('dutiable_email_ids');

    expect($period->fresh()->additional_email)->toBe('existing@vusa.lt');
    $this->assertModelExists($this->source);
})->with([false, true]);

test('a checked email survives collapsing overlapping periods', function (): void {
    $this->sourcePeriod->update(['duty_id' => $this->keptDuty->id]);

    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)(['dutiable_email_ids' => [$this->sourcePeriod->id]]))
        ->assertSessionHasNoErrors();

    expect($this->kept->dutiables()->where('duty_id', $this->keptDuty->id)->sole()->additional_email)->toBe($this->source->email);
});

test('overlapping periods cannot receive two selected source emails and roll back together', function (): void {
    $other = User::factory()->create(['name' => 'Jonas Jonaitis']);
    $otherPeriod = Dutiable::factory()->state(['additional_email' => null, 'additional_photo' => null, 'additional_photo_focal_point' => null])->forUser($other)->forDuty($this->sourceDuty)->create(['start_date' => '2024-08-01', 'end_date' => null]);
    $payload = ($this->payload)([
        'source_user_ids' => [$this->source->id, $other->id],
        'dutiable_email_ids' => [$this->sourcePeriod->id, $otherPeriod->id],
    ]);

    asUser($this->actor)->post(route('users.mergeUsers'), $payload)->assertSessionHasErrors('dutiable_email_ids');

    expect($this->sourcePeriod->fresh()->additional_email)->toBeNull()
        ->and($otherPeriod->fresh()->additional_email)->toBeNull();
    $this->assertModelExists($this->source);
    $this->assertModelExists($other);
});

test('email selections cannot reference the kept account or an unrelated account', function (bool $unrelated): void {
    $period = $this->keptPeriod;
    if ($unrelated) {
        $other = User::factory()->create();
        $period = Dutiable::factory()->state(['additional_email' => null, 'additional_photo' => null, 'additional_photo_focal_point' => null])->forUser($other)->forDuty($this->sourceDuty)->create();
    }

    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)(['dutiable_email_ids' => [$period->id]]))
        ->assertForbidden();

    expect($period->fresh()->additional_email)->toBeNull();
    $this->assertModelExists($this->source);
})->with([false, true]);

test('merge permission does not grant permission to change duty contact details', function (): void {
    $actor = makeUser($this->tenant);
    $role = Role::create(['name' => 'User merge reviewer', 'guard_name' => 'web']);
    $role->givePermissionTo(['users.read.*', 'users.update.*', 'users.delete.*']);
    $actor->assignRole($role);

    asUser($actor)->postJson(route('api.v1.admin.users.mergePreview'), ($this->payload)())
        ->assertOk()->assertJsonCount(0, 'data');
    asUser($actor)->post(route('users.mergeUsers'), ($this->payload)(['dutiable_email_ids' => [$this->sourcePeriod->id]]))
        ->assertForbidden();
});

test('the chosen login email is not offered as an additional email', function (): void {
    $payload = ($this->payload)(['login_email' => $this->source->email]);

    asUser($this->actor)->postJson(route('api.v1.admin.users.mergePreview'), $payload)
        ->assertOk()->assertJsonCount(0, 'data');
});

test('source profile photos automatically become independent duty photos with their focal point', function (): void {
    Storage::fake('spatieMediaLibrary');
    $original = app(AddImageMedia::class)->execute($this->source, UploadedFile::fake()->image('source.jpg'), 'profile_photo', properties: ['focal_point' => '20% 30%']);
    $past = Dutiable::factory()->state(['additional_email' => null, 'additional_photo' => null, 'additional_photo_focal_point' => null])->forUser($this->source)->forDuty($this->sourceDuty)->create(['start_date' => '2020-01-01', 'end_date' => '2021-01-01']);

    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)())->assertSessionHasNoErrors();

    $photos = collect([$this->sourcePeriod, $past])->map(fn (Dutiable $period) => $period->fresh()->getFirstMedia('photo'));
    expect($photos->pluck('id')->unique())->toHaveCount(2);
    foreach ($photos as $photo) {
        expect($photo->id)->not->toBe($original->id)
            ->and($photo->getCustomProperty('focal_point'))->toBe('20% 30%');
        Storage::disk('spatieMediaLibrary')->assertExists($photo->getPathRelativeToRoot());
    }
});

test('existing duty photos take precedence even when a source period starts earlier', function (bool $overlap): void {
    Storage::fake('spatieMediaLibrary');
    app(AddImageMedia::class)->execute($this->source, UploadedFile::fake()->image('source.jpg'), 'profile_photo');
    $period = $overlap ? $this->keptPeriod : $this->sourcePeriod;
    $existing = app(AddImageMedia::class)->execute($period, UploadedFile::fake()->image('existing.jpg'), 'photo', properties: ['focal_point' => '70% 80%']);
    if ($overlap) {
        $this->sourcePeriod->update(['duty_id' => $this->keptDuty->id, 'start_date' => '2023-01-01']);
    }

    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)())->assertSessionHasNoErrors();

    $survivor = $this->kept->dutiables()->where('duty_id', $period->duty_id)->sole();
    expect($survivor->getFirstMedia('photo')->id)->toBe($existing->id)
        ->and($survivor->additional_photo_focal_point)->toBe('70% 80%');
})->with([false, true]);

test('legacy profile photo references remain paired with their focal point on duty periods', function (): void {
    $this->source->update(['profile_photo_path' => '/missing-legacy-photo.jpg', 'profile_photo_focal_point' => '10% 20%']);

    asUser($this->actor)->post(route('users.mergeUsers'), ($this->payload)())->assertSessionHasNoErrors();

    expect($this->sourcePeriod->fresh()->additional_photo)->toBe('/missing-legacy-photo.jpg')
        ->and($this->sourcePeriod->fresh()->additional_photo_focal_point)->toBe('10% 20%');
});
