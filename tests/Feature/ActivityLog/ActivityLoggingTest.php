<?php

use App\Models\Comment;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Training;
use App\Models\Traits\LogsModelActivity;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

/**
 * Every concrete class under App\Models that uses LogsModelActivity, discovered
 * by scanning the directory rather than hand-maintaining a list -- this is the
 * regression net for the "some models silently log nothing" bug: any model
 * added later that ends up with an empty attributesToBeLogged() fails here
 * immediately instead of being discovered by an admin months from now.
 *
 * @return array<class-string, array{0: class-string}>
 */
function loggedModelClasses(): array
{
    $classes = [];

    // Computed with plain SPL iterators, not the File facade/app_path(): Pest
    // resolves dataset closures before the Laravel application is fully
    // bootstrapped, so facades are not yet available at this point.
    $modelsPath = dirname(__DIR__, 3).'/app/Models';

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modelsPath, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $relative = substr($file->getPathname(), strlen($modelsPath) + 1, -4);
        $class = 'App\\Models\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isEnum()) {
            continue;
        }

        if (in_array(LogsModelActivity::class, class_uses_recursive($class), true)) {
            $classes[$class] = [$class];
        }
    }

    return $classes;
}

dataset('loggedModelClasses', fn () => loggedModelClasses());

test('every model using LogsModelActivity has a non-empty attributesToBeLogged', function (string $class): void {
    $model = new $class;

    // A freshly instantiated model has no attributes set, and logUnguarded()
    // enumerates *currently set* attribute keys rather than the schema, so an
    // empty model always reports []. Hydrate from the real schema (no DB writes,
    // no FK/relation setup needed) so the check reflects what actually gets
    // logged once the model is loaded from the database.
    $model->setRawAttributes(array_fill_keys(Schema::getColumnListing($model->getTable()), null));

    expect($model->attributesToBeLogged())->not->toBeEmpty();
})->with('loggedModelClasses');

test('a bare touch does not create any activity', function (): void {
    $meeting = Meeting::factory()->create();
    $before = Activity::count();

    $meeting->touch();

    expect(Activity::count())->toBe($before);
});

test('saving a vote only logs an activity for the vote, not the agenda item or meeting it touches', function (): void {
    $meeting = Meeting::factory()->create();
    $agendaItem = AgendaItem::factory()->for($meeting, 'meeting')->create();
    $vote = Vote::factory()->for($agendaItem, 'agendaItem')->create();

    Activity::query()->delete();

    $vote->update(['decision' => 'positive']);

    expect(Activity::where('subject_type', Vote::class)->where('subject_id', $vote->id)->count())->toBe(1)
        ->and(Activity::where('subject_type', AgendaItem::class)->count())->toBe(0)
        ->and(Activity::where('subject_type', Meeting::class)->count())->toBe(0);
});

test('Training logs attribute changes despite declaring #[Fillable] instead of $guarded', function (): void {
    $training = Training::factory()->create();

    $created = Activity::where('subject_type', Training::class)->where('subject_id', $training->id)->first();
    expect($created)->not->toBeNull()
        ->and(data_get($created, 'attribute_changes.attributes'))->not->toBeEmpty();

    $training->update(['address' => 'Updated Address']);

    $updated = Activity::where('subject_type', Training::class)
        ->where('subject_id', $training->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect(data_get($updated, 'attribute_changes.attributes.address'))->toBe('Updated Address');
});

test('User logs attribute changes despite declaring #[Fillable] instead of $guarded', function (): void {
    $user = User::factory()->create();

    Activity::query()->delete();

    $user->update(['name' => 'Updated Name']);

    $updated = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->latest('id')->first();

    expect(data_get($updated, 'attribute_changes.attributes.name'))->toBe('Updated Name');
});

test('a password-only change logs no activity at all', function (): void {
    $user = User::factory()->create();

    Activity::query()->delete();

    $user->update(['password' => bcrypt('a-new-password')]);

    expect(Activity::where('subject_type', User::class)->where('subject_id', $user->id)->count())->toBe(0);
});

test('password never appears in a User activity even when changed alongside a logged attribute', function (): void {
    $user = User::factory()->create();

    Activity::query()->delete();

    $user->update(['name' => 'Updated Name', 'password' => bcrypt('a-new-password')]);

    $activity = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and(data_get($activity, 'attribute_changes.attributes'))->not->toHaveKey('password')
        ->and(data_get($activity, 'attribute_changes.old'))->not->toHaveKey('password');
});

test('Comment does not log activity', function (): void {
    $meeting = Meeting::factory()->create();

    Comment::factory()->create([
        'commentable_type' => Meeting::class,
        'commentable_id' => $meeting->id,
    ]);

    expect(Activity::where('subject_type', Comment::class)->count())->toBe(0);
});

test('InstitutionCheckIn does not log activity', function (): void {
    InstitutionCheckIn::factory()->create();

    expect(Activity::where('subject_type', InstitutionCheckIn::class)->count())->toBe(0);
});

test('a soft delete logs a deleted event with the snapshot under old and no attributes key', function (): void {
    $meeting = Meeting::factory()->create();
    Activity::query()->delete();

    $meeting->delete();

    $activity = Activity::where('subject_type', Meeting::class)
        ->where('subject_id', $meeting->id)
        ->where('event', 'deleted')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes->has('old'))->toBeTrue()
        ->and($activity->attribute_changes->has('attributes'))->toBeFalse();
});

test('restoring a soft-deleted model logs a restored event and no spurious updated event', function (): void {
    $meeting = Meeting::factory()->create();
    $meeting->delete();
    Activity::query()->delete();

    $meeting->restore();

    $activities = Activity::where('subject_type', Meeting::class)->where('subject_id', $meeting->id)->get();

    expect($activities->pluck('event')->all())->toBe(['restored']);
});
