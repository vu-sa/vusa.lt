<?php

use App\Actions\CollapseOverlappingDutiables;
use App\Actions\Media\AddImageMedia;
use App\Actions\Media\SyncImageMedia;
use App\Actions\MergeUsers;
use App\Models\Approval;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\PendingUpload;
use App\Models\Pivots\Dutiable;
use App\Models\Problem;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->institution = $this->admin->duties()->first()->institution;
});

/** Duplicates carry the same name; the merge refuses anything else. */
function makeNamedUser(Tenant $tenant, string $name = 'Jonas Jonaitis'): User
{
    $user = makeUser($tenant);
    $user->forceFill(['name' => $name])->save();

    return $user;
}

function mergeUsers(User $actor, User $kept, User|array $sources, ?string $loginEmail = null, array $extra = [])
{
    $sources = is_array($sources) ? $sources : [$sources];

    return asUser($actor)->post(route('users.mergeUsers'), [
        'kept_user_id' => $kept->id,
        'source_user_ids' => collect($sources)->pluck('id')->all(),
        'login_email' => $loginEmail ?? $kept->email,
        ...$extra,
    ]);
}

describe('authorization', function (): void {
    test('cannot merge without users update + delete permissions', function (): void {
        $outsider = makeUser($this->tenant);
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $response = mergeUsers($outsider, $kept, $merged);

        expect($response->status())->toBe(403)
            ->and(User::find($merged->id))->not->toBeNull();
    });

    test('cannot merge a record that belongs to a unit the admin does not manage', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser(Tenant::query()->whereKeyNot($this->tenant->id)->first());

        expect(mergeUsers($this->admin, $kept, $merged)->status())->toBe(403)
            ->and(User::find($merged->id))->not->toBeNull();
    });
});

describe('merge guard', function (): void {
    test('refuses records whose names do not match', function (): void {
        $kept = makeNamedUser($this->tenant, 'Jonas Jonaitis');
        $merged = makeNamedUser($this->tenant, 'Petras Petraitis');

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasErrors('source_user_ids.0');

        expect(User::find($merged->id))->not->toBeNull();
    });

    test('accepts the same name in another order, with diacritics or a middle name', function (string $otherName): void {
        $kept = makeNamedUser($this->tenant, 'Jonas Čiurlionis');
        $merged = makeNamedUser($this->tenant, $otherName);

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasNoErrors();

        expect(User::withTrashed()->find($merged->id))->toBeNull();
    })->with(['Ciurlionis Jonas', 'Jonas Petras Čiurlionis']);

    test('a super admin may merge records whose names differ', function (): void {
        $kept = makeNamedUser($this->tenant, 'Jonas Jonaitis');
        $merged = makeNamedUser($this->tenant, 'Petras Petraitis');

        mergeUsers(makeAdminUser($this->tenant), $kept, $merged)->assertSessionHasNoErrors();

        expect(User::withTrashed()->find($merged->id))->toBeNull();
    });

    test('refuses a source with a directly assigned role, even for a super admin', function (string $actorRole): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);
        $merged->assignRole(Role::query()->where('name', 'Komunikacijos koordinatorius')->firstOrFail());

        // Holding a direct role puts the record beyond a unit admin, so use the central role.
        $actor = $actorRole === 'super admin'
            ? makeAdminUser($this->tenant)
            : makeTenantUserWithRole('Centrinio biuro komunikacijos koordinatorius', $this->tenant);

        mergeUsers($actor, $kept, $merged)->assertSessionHasErrors('source_user_ids.0');

        expect(User::find($merged->id))->not->toBeNull();
    })->with(['central coordinator', 'super admin']);

    test('rejects duplicate source user ids without deleting the source', function (): void {
        $kept = makeNamedUser($this->tenant);
        $source = makeNamedUser($this->tenant);

        mergeUsers($this->admin, $kept, [$source, $source])->assertSessionHasErrors('source_user_ids.1');

        expect($source->fresh()->trashed())->toBeFalse();
    });

    test('rejects merging a user with themselves', function (): void {
        $kept = makeNamedUser($this->tenant);

        mergeUsers($this->admin, $kept, $kept)->assertSessionHasErrors('source_user_ids.0');
    });
});

describe('login email', function (): void {
    test('cannot change a protected kept account login through a merge', function (string $protection): void {
        $kept = makeNamedUser($this->tenant);
        $source = makeNamedUser($this->tenant);

        if ($protection === 'shared') {
            $other = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
            $kept->duties()->attach(Duty::factory()->for(Institution::factory()->for($other))->create(), ['start_date' => now()->subDay()]);
        } else {
            $kept->assignRole(config('permission.super_admin_role_name'));
        }

        expect($this->admin->can('update', $kept))->toBeTrue()
            ->and($this->admin->can('updateIdentity', $kept))->toBeFalse();
        $email = $kept->email;

        mergeUsers($this->admin, $kept, $source, $source->email)->assertForbidden();

        expect($kept->fresh()->email)->toBe($email);
        $this->assertModelExists($source);
    })->with(['shared', 'super admin']);

    test('can merge into a protected account when keeping its login unchanged', function (): void {
        $kept = makeNamedUser($this->tenant);
        $kept->assignRole(config('permission.super_admin_role_name'));
        $source = makeNamedUser($this->tenant);

        mergeUsers($this->admin, $kept, $source)->assertSessionHasNoErrors();

        expect($kept->fresh()->email)->toBe($kept->email);
        $this->assertModelMissing($source);
    });

    test('a super admin can choose a source login for another super admin', function (): void {
        $kept = makeNamedUser($this->tenant);
        $kept->assignRole(config('permission.super_admin_role_name'));
        $source = makeNamedUser($this->tenant);

        mergeUsers(makeAdminUser($this->tenant), $kept, $source, $source->email)->assertSessionHasNoErrors();

        expect($kept->fresh()->email)->toBe($source->email);
    });

    test('the kept record signs in with the chosen address of a merged record', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);
        $mergedEmail = $merged->email;

        mergeUsers($this->admin, $kept, $merged, $mergedEmail)->assertSessionHasNoErrors();

        expect($kept->fresh()->email)->toBe($mergedEmail)
            ->and(User::withTrashed()->find($merged->id))->toBeNull();
    });

    test('rejects an address that belongs to none of the merged records', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        mergeUsers($this->admin, $kept, $merged, 'someone.else@vusa.lt')->assertSessionHasErrors('login_email');
    });
});

describe('merging dutiables', function (): void {
    test('merges multiple source users into one kept user', function (): void {
        $kept = makeNamedUser($this->tenant);
        $firstSource = makeNamedUser($this->tenant);
        $secondSource = makeNamedUser($this->tenant);

        mergeUsers($this->admin, $kept, [$firstSource, $secondSource])->assertRedirect();

        expect(User::withTrashed()->find($firstSource->id))->toBeNull()
            ->and(User::withTrashed()->find($secondSource->id))->toBeNull()
            ->and(User::find($kept->id))->not->toBeNull();
    });

    test('repoints the merged user assignments onto the kept user', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $mergedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        Dutiable::factory()->forDuty($mergedDuty)->forUser($merged)->create([
            'start_date' => '2020-01-01',
            'end_date' => '2020-12-31',
        ]);
        Dutiable::factory()->forDuty($mergedDuty)->forUser($merged)->create([
            'start_date' => '2025-01-01',
            'end_date' => null,
        ]);

        mergeUsers($this->admin, $kept, $merged)->assertRedirect();

        expect(Dutiable::where('dutiable_id', $merged->id)->count())->toBe(0)
            ->and(Dutiable::where('dutiable_id', $kept->id)->where('duty_id', $mergedDuty->id)->count())->toBe(2);
    });

    test('collapses overlapping assignments onto the kept user when both shared a duty', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $sharedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        // Kept user holds the duty, open-ended.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($kept)->create([
            'start_date' => '2024-01-01',
            'end_date' => null,
            'additional_email' => null,
        ]);

        // Merged user holds the same duty, overlapping, carrying an email the
        // survivor is missing.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($merged)->create([
            'start_date' => '2024-06-01',
            'end_date' => '2024-12-01',
            'additional_email' => 'kept@vusa.lt',
        ]);

        mergeUsers($this->admin, $kept, $merged);

        $rows = Dutiable::where('duty_id', $sharedDuty->id)->where('dutiable_id', $kept->id)->get();

        expect($rows)->toHaveCount(1);
        $survivor = $rows->first();
        expect($survivor->start_date->toDateString())->toBe('2024-01-01')
            ->and($survivor->end_date)->toBeNull()
            ->and($survivor->additional_email)->toBe('kept@vusa.lt');
    });

    test('a collapsed assignment keeps the photo only the merged row had', function (): void {
        Storage::fake('spatieMediaLibrary');
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);
        $sharedDuty = Duty::factory()->create(['institution_id' => $this->institution->id]);

        Dutiable::factory()->forDuty($sharedDuty)->forUser($kept)->create(['start_date' => '2024-01-01', 'end_date' => null]);
        $mergedRow = Dutiable::factory()->forDuty($sharedDuty)->forUser($merged)->create(['start_date' => '2024-06-01', 'end_date' => '2024-12-01']);
        $photo = app(AddImageMedia::class)->execute($mergedRow, UploadedFile::fake()->image('seat.png'), 'photo', properties: ['focal_point' => '20% 30%']);

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasNoErrors();

        $survivor = Dutiable::where('duty_id', $sharedDuty->id)->where('dutiable_id', $kept->id)->sole();

        expect($survivor->getFirstMedia('photo')?->id)->toBe($photo->id)
            ->and($survivor->additional_photo)->toContain('/'.$photo->id.'/')
            ->and($survivor->additional_photo_focal_point)->toBe('20% 30%');
    });

    test('the winning media replaces stale cached photo values after a collapse', function (bool $survivorHasMedia): void {
        Storage::fake('spatieMediaLibrary');
        $duty = $this->admin->duties()->first();
        $survivor = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create([
            'additional_photo' => '/stale.jpg', 'additional_photo_focal_point' => '90% 90%',
        ]);
        $loser = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create();
        $photo = app(AddImageMedia::class)->execute($survivorHasMedia ? $survivor : $loser, UploadedFile::fake()->image('winner.jpg'), 'photo', properties: ['focal_point' => '10% 20%']);

        DB::transaction(fn () => CollapseOverlappingDutiables::foldInto($survivor, $loser));

        expect($survivor->fresh()->getFirstMedia('photo')->id)->toBe($photo->id)
            ->and($survivor->fresh()->additional_photo)->toContain('/'.$photo->id.'/')
            ->and($survivor->fresh()->additional_photo_focal_point)->toBe('10% 20%');
    })->with([false, true]);

    test('legacy photo and focal point remain paired during a collapse', function (bool $survivorHasPhoto): void {
        $duty = $this->admin->duties()->first();
        $survivor = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create([
            'additional_photo' => $survivorHasPhoto ? '/kept.jpg' : null,
            'additional_photo_focal_point' => null,
        ]);
        $loser = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create([
            'additional_photo' => '/loser.jpg', 'additional_photo_focal_point' => '70% 80%',
        ]);

        DB::transaction(fn () => CollapseOverlappingDutiables::foldInto($survivor, $loser));

        expect($survivor->fresh()->additional_photo)->toBe($survivorHasPhoto ? '/kept.jpg' : '/loser.jpg')
            ->and($survivor->fresh()->additional_photo_focal_point)->toBe($survivorHasPhoto ? null : '70% 80%');
    })->with([false, true]);

    test('rolling back a collapse restores media ownership and leaves its files intact', function (): void {
        Storage::fake('spatieMediaLibrary');
        $duty = $this->admin->duties()->first();
        $survivor = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create(['additional_photo' => '/kept.jpg']);
        $loser = Dutiable::factory()->forDuty($duty)->forUser($this->admin)->create();
        $photo = app(AddImageMedia::class)->execute($loser, UploadedFile::fake()->image('rollback.jpg'), 'photo');
        $path = $photo->getPathRelativeToRoot();

        DB::beginTransaction();
        CollapseOverlappingDutiables::foldInto($survivor, $loser);
        $loser->delete();
        DB::rollBack();

        expect($photo->fresh()->model_id)->toBe($loser->id)
            ->and($survivor->fresh()->additional_photo)->toBe('/kept.jpg');
        Storage::disk('spatieMediaLibrary')->assertExists($path);
    });

    test('does not collapse two genuinely separate stints on the same duty', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $sharedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        Dutiable::factory()->forDuty($sharedDuty)->forUser($kept)->create([
            'start_date' => '2020-01-01',
            'end_date' => '2020-12-31',
        ]);
        Dutiable::factory()->forDuty($sharedDuty)->forUser($merged)->create([
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
        ]);

        mergeUsers($this->admin, $kept, $merged);

        expect(Dutiable::where('duty_id', $sharedDuty->id)->where('dutiable_id', $kept->id)->count())->toBe(2);
    });

    test('keeps owning-tenant and cross-tenant rows for the same duty as separate assignments', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $sharedDuty = Duty::factory()->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
            'institution_id' => $this->institution->id,
        ]);

        // Kept holds the duty as a regular (owning-tenant) member.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($kept)->create([
            'start_date' => '2024-01-01',
            'end_date' => null,
            'tenant_id' => null,
        ]);

        // Merged holds the same duty as a cross-tenant rep — a distinct scope
        // that must survive the merge rather than being folded into the owning row.
        Dutiable::factory()->forDuty($sharedDuty)->forUser($merged)->forTenant($this->tenant)->create([
            'start_date' => '2024-06-01',
            'end_date' => null,
        ]);

        mergeUsers($this->admin, $kept, $merged);

        $rows = Dutiable::where('duty_id', $sharedDuty->id)
            ->where('dutiable_id', $kept->id)
            ->orderBy('tenant_id')
            ->get();

        expect($rows)->toHaveCount(2)
            ->and($rows->pluck('tenant_id')->unique()->values()->all())->toBe([null, $this->tenant->id]);
    });

    test('the kept user holds the merged duties permissions straight away', function (): void {
        $kept = makeNamedUser($this->tenant);
        // Nothing for the kept record to take over, so no save of it clears the cache by accident.
        $kept->forceFill(['phone' => '+37060000000', 'profile_photo_path' => '/p.jpg', 'profile_photo_focal_point' => '50% 50%'])->save();
        $merged = makeNamedUser($this->tenant);
        $duty = Duty::factory()->create(['institution_id' => $this->institution->id]);
        $duty->assignRole('Komunikacijos koordinatorius');
        Dutiable::factory()->forDuty($duty)->forUser($merged)->create(['start_date' => now()->subDay(), 'end_date' => null]);

        // Warm the kept user's cached duties before the merge.
        expect(app(ModelAuthorizer::class)->allows($kept, 'news.update.padalinys'))->toBeFalse();

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasNoErrors();

        expect(app(ModelAuthorizer::class)->allows($kept->fresh(), 'news.update.padalinys'))->toBeTrue();
    });
});

describe('transferring related records', function (): void {
    test('adopts profile media without changing its files or focal point', function (): void {
        Storage::fake('spatieMediaLibrary');
        $kept = makeNamedUser($this->tenant);
        $source = makeNamedUser($this->tenant);
        $kept->forceFill(['profile_photo_path' => null, 'profile_photo_focal_point' => '90% 90%'])->save();
        $photo = app(AddImageMedia::class)->execute($source, UploadedFile::fake()->image('profile.jpg'), 'profile_photo', properties: ['focal_point' => '20% 30%']);
        $path = $photo->getPathRelativeToRoot();

        mergeUsers($this->admin, $kept, $source)->assertSessionHasNoErrors();

        expect($photo->fresh()->model_id)->toBe($kept->id)
            ->and($kept->fresh()->getFirstMedia('profile_photo')?->id)->toBe($photo->id)
            ->and($kept->fresh()->profile_photo_focal_point)->toBe('20% 30%');
        Storage::disk('spatieMediaLibrary')->assertExists($path);
    });

    test('keeps an existing profile photo instead of the source photo', function (): void {
        Storage::fake('spatieMediaLibrary');
        $kept = makeNamedUser($this->tenant);
        $source = makeNamedUser($this->tenant);
        $photo = app(AddImageMedia::class)->execute($kept, UploadedFile::fake()->image('kept.jpg'), 'profile_photo', properties: ['focal_point' => '10% 20%']);
        app(AddImageMedia::class)->execute($source, UploadedFile::fake()->image('source.jpg'), 'profile_photo', properties: ['focal_point' => '70% 80%']);

        mergeUsers($this->admin, $kept, $source)->assertSessionHasNoErrors();

        expect($kept->fresh()->getFirstMedia('profile_photo')?->id)->toBe($photo->id)
            ->and($kept->fresh()->profile_photo_focal_point)->toBe('10% 20%');
    });

    test('legacy profile photos and their focal points remain paired', function (bool $hasPhoto): void {
        $kept = makeNamedUser($this->tenant);
        $source = makeNamedUser($this->tenant);
        $kept->forceFill(['profile_photo_path' => $hasPhoto ? '/kept.jpg' : null, 'profile_photo_focal_point' => null])->save();
        $source->forceFill(['profile_photo_path' => '/source.jpg', 'profile_photo_focal_point' => '70% 80%'])->save();

        mergeUsers($this->admin, $kept, $source)->assertSessionHasNoErrors();

        expect($kept->fresh()->profile_photo_path)->toBe($hasPhoto ? '/kept.jpg' : '/source.jpg')
            ->and($kept->fresh()->profile_photo_focal_point)->toBe($hasPhoto ? null : '70% 80%');
    })->with([false, true]);

    test('rolling back a merge restores profile media ownership and its files', function (): void {
        Storage::fake('spatieMediaLibrary');
        $kept = makeNamedUser($this->tenant);
        $source = makeNamedUser($this->tenant);
        $kept->forceFill(['profile_photo_path' => null])->save();
        $photo = app(AddImageMedia::class)->execute($source, UploadedFile::fake()->image('profile.jpg'), 'profile_photo');
        $path = $photo->getPathRelativeToRoot();

        DB::beginTransaction();
        MergeUsers::execute($kept, collect([$source]), $kept->email);
        DB::rollBack();

        $this->assertModelExists($source);
        expect($photo->fresh()->model_id)->toBe($source->id)
            ->and($kept->fresh()->profile_photo_path)->toBeNull();
        Storage::disk('spatieMediaLibrary')->assertExists($path);
    });

    test('transfers pending uploads so draft images can still be attached', function (): void {
        Storage::fake('spatieMediaLibrary');
        $kept = makeNamedUser($this->tenant);
        $source = makeNamedUser($this->tenant);
        $upload = PendingUpload::create(['user_id' => $source->id]);
        $photo = app(AddImageMedia::class)->execute($upload, UploadedFile::fake()->image('draft.jpg'), PendingUpload::COLLECTION);
        $updated = $upload->updated_at->toDateTimeString();
        DB::table('content_editor_drafts')->insert([
            'user_id' => $source->id, 'kind' => 'news', 'identity' => 'new',
            'snapshot' => json_encode(['image_media' => ['id' => $photo->id]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        mergeUsers($this->admin, $kept, $source)->assertSessionHasNoErrors();

        expect($upload->fresh()->user_id)->toBe($kept->id)
            ->and($upload->fresh()->updated_at->toDateTimeString())->toBe($updated)
            ->and(DB::table('content_editor_drafts')->where('user_id', $kept->id)->value('snapshot'))->toContain((string) $photo->id);
        app(SyncImageMedia::class)->execute($kept->fresh(), 'profile_photo', ['id' => $photo->id], $kept->fresh());
        expect($kept->fresh()->getFirstMedia('profile_photo')?->id)->toBe($photo->id);
    });

    test('repoints reservations onto the kept user, dropping a duplicate the kept user already holds', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $sharedReservation = Reservation::factory()->create();
        $onlyMerged = Reservation::factory()->create();

        // The kept user already holds one of them — that pivot row must survive
        // (not duplicate), and the merged-only one moves across.
        $kept->reservations()->attach($sharedReservation->id);
        $merged->reservations()->attach([$sharedReservation->id, $onlyMerged->id]);

        mergeUsers($this->admin, $kept, $merged);

        $keptReservationIds = DB::table('reservation_user')->where('user_id', $kept->id)->pluck('reservation_id')->all();

        expect($keptReservationIds)->toHaveCount(2)
            ->and($keptReservationIds)->toContain($sharedReservation->id, $onlyMerged->id);
    });

    test('moves authored records, approvals, problems and follows', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $comment = Comment::factory()->for($this->institution, 'commentable')->create(['user_id' => $merged->id]);
        $approval = Approval::factory()->approved()->create(['user_id' => $merged->id]);
        $problem = Problem::factory()->create(['created_by' => $merged->id, 'responsible_user_id' => $merged->id]);
        $merged->followedInstitutions()->attach($this->institution->id);
        $kept->followedInstitutions()->attach($this->institution->id);

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasNoErrors();

        expect($comment->fresh()->user_id)->toBe($kept->id)
            ->and($approval->fresh()->user_id)->toBe($kept->id)
            ->and($problem->fresh()->created_by)->toBe($kept->id)
            ->and($problem->fresh()->responsible_user_id)->toBe($kept->id)
            ->and(DB::table('institution_follows')->where('user_id', $kept->id)->count())->toBe(1);
    });

    test('keeps one reaction when both records reacted the same way, and rewrites mentions', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        $comment = Comment::factory()->for($this->institution, 'commentable')->create(['mentioned_user_ids' => [$merged->id]]);
        CommentReaction::factory()->create(['comment_id' => $comment->id, 'user_id' => $kept->id, 'emoji' => '👍']);
        CommentReaction::factory()->create(['comment_id' => $comment->id, 'user_id' => $merged->id, 'emoji' => '👍']);
        CommentReaction::factory()->create(['comment_id' => $comment->id, 'user_id' => $merged->id, 'emoji' => '🎉']);

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasNoErrors();

        expect(CommentReaction::query()->where('user_id', $kept->id)->pluck('emoji')->sort()->values()->all())->toBe(['🎉', '👍'])
            ->and($comment->fresh()->mentioned_user_ids)->toBe([$kept->id]);
    });

    test('fills profile fields the kept record is missing', function (): void {
        $kept = makeNamedUser($this->tenant);
        $kept->forceFill(['phone' => null])->save();
        $merged = makeNamedUser($this->tenant);
        $merged->forceFill(['phone' => '+37060000000'])->save();

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasNoErrors();

        expect($kept->fresh()->phone)->toBe('+37060000000');
    });
});

describe('after merging', function (): void {
    test('the merged record is deleted permanently and the merge is logged', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        mergeUsers($this->admin, $kept, $merged)->assertSessionHasNoErrors();

        $activity = Activity::query()->where('event', 'users_merged')->sole();

        expect(User::withTrashed()->find($merged->id))->toBeNull()
            ->and($activity->subject_id)->toBe($kept->id)
            ->and($activity->causer_id)->toBe($this->admin->id)
            ->and($activity->properties['merged'][0]['email'])->toBe($merged->email);
    });

    test('can send the admin on to the kept record', function (): void {
        $kept = makeNamedUser($this->tenant);
        $merged = makeNamedUser($this->tenant);

        mergeUsers($this->admin, $kept, $merged, extra: ['redirect_to_kept' => true])
            ->assertRedirect(route('users.edit', $kept));
    });
});

describe('duplicates on the edit form', function (): void {
    test('lists other records of the same name once the deferred prop loads', function (): void {
        $user = makeNamedUser($this->tenant);
        $duplicate = makeNamedUser($this->tenant);
        makeNamedUser($this->tenant, 'Petras Petraitis');

        asUser($this->admin)->get(route('users.edit', $user))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing('duplicates')
                ->loadDeferredProps(fn ($page) => $page
                    ->has('duplicates', 1)
                    ->where('duplicates.0.id', $duplicate->id)
                    ->where('duplicates.0.email', $duplicate->email)
                    ->where('duplicates.0.keepable_ids', [$user->id, $duplicate->id])
                    ->where('duplicates.0.blocked_reason', null)));
    });

    test('explains why an out-of-scope duplicate cannot be merged', function (): void {
        $user = makeNamedUser($this->tenant);
        $duplicate = makeNamedUser(Tenant::query()->whereKeyNot($this->tenant->id)->first());

        asUser($this->admin)->get(route('users.edit', $user))
            ->assertInertia(fn ($page) => $page
                ->loadDeferredProps(fn ($page) => $page
                    ->where('duplicates.0.keepable_ids', [])
                    ->where('duplicates.0.email', null)
                    ->where('duplicates.0.blocked_reason', fn (?string $reason) => filled($reason))));
    });
});
